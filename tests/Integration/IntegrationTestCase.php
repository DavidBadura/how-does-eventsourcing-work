<?php declare(strict_types=1);

namespace App\Tests\Integration;


use App\Domain\Hotel\HotelRepository;
use App\Domain\UuidGenerator;
use App\EventSourcing\ProjectionRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

abstract class IntegrationTestCase extends WebTestCase
{
    protected static $booted = false;
    /** @var KernelInterface */
    protected static $kernel;
    /** @var  KernelBrowser */
    protected static $client;

    protected function setUp(): void
    {
        parent::setUp();

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        if (self::$booted) {
            $this->purgeDb();
        } else {
            $this->resetDb();
            self::$booted = true;
        }

        $this->clearProjections();

        static::ensureKernelShutdown();

        static::$client = static::createClient();

        UuidGenerator::dummy(true);
        mt_srand(42);
    }

    protected function tearDown(): void
    {
        $em = static::getService('doctrine.orm.default_entity_manager');
        $em->clear();
        $em->getConnection()->close();

        parent::tearDown();
        static::ensureKernelShutdown();

        static::$kernel = null;
        static::$client = null;
    }

    protected function resetDb()
    {
        $this->runCommand('doctrine:database:drop', ['--force' => true]);
        $this->runCommand('doctrine:database:create');
        $this->runCommand('doctrine:schema:create');
        $this->runCommand('doctrine:database:drop', ['--force' => true, '--connection' => 'eventstore']);
        $this->runCommand('doctrine:database:create', ['--connection' => 'eventstore']);
        $this->runCommand('event-store:create');
    }

    protected function purgeDb()
    {
        $this->clearDatabase(
            static::$kernel->getContainer()->get('doctrine.orm.entity_manager')->getConnection()
        );

        $this->clearDatabase(
            static::$kernel->getContainer()->get('doctrine.dbal.eventstore_connection')
        );
    }

    protected function clearDatabase(Connection $connection): void
    {
        $connection->exec('SET foreign_key_checks=0;');

        $sql = [];
        foreach ($connection->fetchAll('SHOW TABLES;') as $tableRow) {
            $sql[] .= 'truncate ' . current($tableRow);
        }

        if (count($sql) === 0) {
            return;
        }

        $connection->exec(implode(';', $sql));
        $connection->exec('SET foreign_key_checks=1;');
    }

    protected function clearProjections()
    {
        /** @var ProjectionRepository $repositiory */
        $repositiory = static::getService(ProjectionRepository::class);
        $repositiory->drop();
    }

    protected function runCommand(string $commandName, array $parameters = [])
    {
        $baseParameters = [
            '--env' => 'test',
            '--quiet' => null,
            'command' => $commandName,
        ];

        $this->getConsoleApplication(static::$kernel)->run(
            new ArrayInput(array_merge($baseParameters, $parameters)),
            new NullOutput()
        );
    }

    private function getConsoleApplication(KernelInterface $kernel): Application
    {
        $app = new Application($kernel);
        $app->setAutoExit(false);
        $app->setCatchExceptions(false);

        return $app;
    }

    protected static function assertSuccessful()
    {
        $response = static::$client->getResponse();

        if (!$response->isSuccessful()) {
            /** @var ExceptionCollector $collector */
            $collector = static::getService(ExceptionCollector::class);
            $exception = $collector->peek();

            if ($exception) {
                throw $exception;
            }
        }

        self::assertTrue($response->isSuccessful(), 'got ' . $response->getStatusCode());
    }

    protected static function assertClientError(int $statusCode = 400)
    {
        $response = static::$client->getResponse();
        $message = $response->isClientError() ? '' : self::extractMessage();

        self::assertEquals($statusCode, $response->getStatusCode(), $message);
    }

    /** @deprecated */
    protected static function assertExceptionOccurred(string $exception, string $exceptionMessage = '')
    {
        /** @var ExceptionCollector $collector */
        $collector = static::getService(ExceptionCollector::class);
        $entry = $collector->peek();

        self::assertNotNull($entry);

        if ($entry instanceof HandlerFailedException) {
            $entry = $entry->getPrevious(); // ugly hack
        }

        if (get_class($entry) !== $exception) {
            throw $entry;
        }

        self::assertRegExp(sprintf('#%s#', $exceptionMessage), $entry->getMessage());
    }

    protected static function assertStatusCode(int $statusCode)
    {
        $response = static::$client->getResponse();
        $message = $response->isSuccessful() ? '' : self::extractMessage();

        self::assertEquals($statusCode, $response->getStatusCode(), $message);
    }

    protected static function post(string $url, array $data = [])
    {
        static::$client->request('POST', $url, [], [], [], json_encode($data));

        $response = static::$client->getResponse();

        if (strpos($response->headers->get('Content-Type'), 'application/json') !== false) {
            return json_decode($response->getContent(), true);
        }

        return null;
    }

    protected static function postCommand(string $commandClass, array $data = [])
    {
        list(, , $domain, , $command) = explode('\\', $commandClass);

        return static::post(
            '/message',
            [
                'command' => sprintf('%s:%s', $domain, $command),
                'payload' => $data,
            ]
        );
    }

    protected static function get(string $url)
    {
        static::$client->request('GET', $url);

        $response = static::$client->getResponse();

        if (strpos($response->headers->get('Content-Type'), 'application/json') !== false) {
            return json_decode($response->getContent(), true);
        }

        return null;
    }

    protected static function login(string $email, string $password)
    {
        $response = static::post(
            '/login',
            [
                'email' => $email,
                'password' => $password,
            ]
        );

        self::assertSuccessful();

        self::$client->setServerParameter('HTTP_X-AUTH-TOKEN', $response['token']);
    }

    protected static function getService(string $id)
    {
        return static::$kernel->getContainer()->get($id);
    }

    protected static function hotelRepository(): HotelRepository
    {
        return static::getService(HotelRepository::class);
    }

    private static function extractMessage(): string
    {
        $response = static::$client->getResponse();
        $message = '';

        if (strpos($response->headers->get('Content-Type'), 'text/html') !== false) {
            $crawler = static::$client->getCrawler();

            if ($crawler) {
                if ($crawler->filter('h1.exception-message')->count()) {
                    $message = $crawler->filter('h1.exception-message')->text();

                    foreach ($crawler->filter('.trace-message') as $node) {
                        $message .= "\n" . $node->textContent;
                    }
                }
            }
        }

        /** @var ExceptionCollector $collector */
        $collector = static::getService(ExceptionCollector::class);
        $exception = $collector->peek();

        if (!$message && $exception) {
            throw $exception;
        }

        return $message ?: $response->getContent();
    }
}
