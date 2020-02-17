<?php declare(strict_types=1);

namespace App\EventSourcing;

use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Stellt sicher, dass die Events in der Richtigen Reihenfolge abgearbeitet werden.
 */
class EventStream
{
    /**
     * @var object[]
     */
    private array $queue;
    private MessageBusInterface $eventBus;
    private bool $process = false;

    public function __construct(MessageBusInterface $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    public function dispatch(object $event): void
    {
        $this->queue[] = $event;

        if ($this->process) {
            return;
        }

        $this->process = true;

        while ($event = array_shift($this->queue)) {
            $this->eventBus->dispatch($event);
        }

        $this->process = false;
    }
}
