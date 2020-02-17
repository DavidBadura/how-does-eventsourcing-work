<?php declare(strict_types=1);

namespace App\EventSourcing;

use Doctrine\DBAL\Connection;

class Store
{
    private Connection $connection;

    public function __construct(Connection $eventConnection)
    {
        $this->connection = $eventConnection;
    }

    /**
     * @return AggregateChanged[]
     */
    public function load(string $aggregate, string $id): array
    {
        $result = $this->connection->fetchAll('
            SELECT * 
            FROM eventstore 
            WHERE aggregate = :aggregate AND aggregateId = :id
        ', [
            'aggregate' => self::shortName($aggregate),
            'id' => $id
        ]);

        return array_map(
            static function (array $data) {
                return AggregateChanged::deserialize($data);
            },
            $result
        );
    }

    /**
     * @return AggregateChanged[]
     */
    public function loadAll(): \Generator
    {
        $result = $this->connection->executeQuery('SELECT * FROM eventstore');

        while ($data = $result->fetch()) {
            yield AggregateChanged::deserialize($data);
        }

        $result->closeCursor();
    }

    public function count(): int
    {
        return (int)$this->connection->fetchColumn('SELECT COUNT(*) FROM eventstore');
    }

    /**
     * @param AggregateChanged[] $events
     */
    public function save(string $aggregate, string $id, array $events): void
    {
        $this->connection->transactional(
            static function (Connection $connection) use ($aggregate, $events) {
                foreach ($events as $event) {
                    $data = $event->serialize();
                    $data['aggregate'] = self::shortName($aggregate);

                    $connection->insert(
                        'eventstore',
                        $data
                    );
                }
            }
        );
    }

    public function create(): void
    {
        $this->connection->query('
            CREATE TABLE IF NOT EXISTS eventstore (
                id INT AUTO_INCREMENT PRIMARY KEY,
                aggregate VARCHAR(255) NOT NULL,
                aggregateId VARCHAR(255) NOT NULL,
                playhead INT NOT NULL,
                event VARCHAR(255) NOT NULL,
                payload JSON NOT NULL,
                recordedOn DATETIME NOT NULL,
                UNIQUE KEY aggregate_key (aggregate, aggregateId, playhead)
            )  
        ');
    }

    public function drop(): void
    {
        $this->connection->query('
            DROP TABLE IF EXISTS eventstore;  
        ');
    }

    private static function shortName(string $name): string
    {
        $parts = explode('\\', $name);
        $shortName = array_pop($parts);

        if (!$shortName) {
            return $name;
        }

        return $shortName;
    }
}
