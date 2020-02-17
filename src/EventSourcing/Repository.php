<?php

declare(strict_types=1);

namespace App\EventSourcing;

use Webmozart\Assert\Assert;

class Repository
{
    private Store $store;
    private EventStream $eventStream;

    /**
     * @psalm-var class-string
     */
    private string $aggregateClass;

    /**
     * @var AggregateRoot[]
     */
    private array $instances = [];

    public function __construct(
        Store $store,
        EventStream $eventStream,
        string $aggregateClass
    ) {
        $this->assertExtendsEventSourcedAggregateRoot($aggregateClass);

        $this->store = $store;
        $this->eventStream = $eventStream;
        $this->aggregateClass = $aggregateClass;
    }

    public function load(string $id): AggregateRoot
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        $events = $this->store->load($this->aggregateClass, $id);

        if (count($events) === 0) {
            throw new AggregateNotFoundException($this->aggregateClass, $id);
        }

        return $this->instances[$id] = $this->createAggregate($this->aggregateClass, $events);
    }

    public function save(AggregateRoot $aggregate): void
    {
        Assert::isInstanceOf($aggregate, $this->aggregateClass);

        $eventStream = $aggregate->releaseEvents();

        if (count($eventStream) === 0) {
            return;
        }

        $this->store->save($this->aggregateClass, $aggregate->aggregateRootId(), $eventStream);

        foreach ($eventStream as $event) {
            $this->eventStream->dispatch($event);
        }
    }

    /**
     * @psalm-assert class-string $class
     */
    private function assertExtendsEventSourcedAggregateRoot(string $class): void
    {
        Assert::subclassOf(
            $class,
            AggregateRoot::class,
            sprintf("Class '%s' is not an EventSourcedAggregateRoot.", $class)
        );
    }

    private function createAggregate(string $class, array $eventStream): AggregateRoot
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('class "%s" not found', $class));
        }

        $reflaction = new \ReflectionClass($class);

        /** @var AggregateRoot $aggregate */
        $aggregate = $reflaction->newInstanceWithoutConstructor();
        $aggregate->initializeState($eventStream);

        return $aggregate;
    }
}
