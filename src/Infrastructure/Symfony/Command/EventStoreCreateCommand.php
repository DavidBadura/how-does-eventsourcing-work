<?php declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\EventSourcing\Store;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventStoreCreateCommand extends Command
{
    /**
     * @var Store
     */
    private $store;

    public function __construct(Store $store)
    {
        parent::__construct();

        $this->store = $store;
    }

    protected function configure(): void
    {
        $this
            ->setName('event-store:create')
            ->setDescription('Creates the event store schema');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->store->create();

        $output->writeln('<info>Event Store created</info>');

        return 0;
    }
}
