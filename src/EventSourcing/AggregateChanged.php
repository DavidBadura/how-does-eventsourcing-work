<?php declare(strict_types=1);

namespace App\EventSourcing;

abstract class AggregateChanged
{
    protected string $aggregateId;
    protected array $payload;
    private ?int $playhead;
    private ?\DateTimeImmutable $recordedOn;

    private function __construct()
    {
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function playhead(): ?int
    {
        return $this->playhead;
    }

    public function recordedOn(): ?\DateTimeImmutable
    {
        return $this->recordedOn;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public static function occur(string $aggregateId, array $payload = []): self
    {
        $event = new static();
        $event->aggregateId = $aggregateId;
        $event->payload = $payload;

        return $event;
    }

    public function recordNow(int $playhead): self
    {
        $event = new static();
        $event->playhead = $playhead;
        $event->aggregateId = $this->aggregateId;
        $event->payload = $this->payload;
        $event->recordedOn = new \DateTimeImmutable();

        return $event;
    }

    public static function deserialize(array $data): self
    {
        $class = $data['event'];

        $event = new $class();
        $event->aggregateId = $data['aggregateId'];
        $event->playhead = $data['playhead'];
        $event->recordedOn = $data['recordedOn'] ? new \DateTimeImmutable($data['recordedOn']) : null;
        $event->payload = json_decode($data['payload'], true);

        if (!$event instanceof self) {
            throw new \InvalidArgumentException();
        }

        return $event;
    }

    public function serialize(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'playhead' => $this->playhead,
            'event' => get_class($this),
            'payload' => json_encode($this->payload),
            'recordedOn' => $this->recordedOn instanceof \DateTimeImmutable ? $this->recordedOn->format('Y-m-d H:i:s') : null,
        ];
    }
}
