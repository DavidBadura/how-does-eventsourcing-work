<?php

namespace App\Domain\Hotel\Event;

use App\EventSourcing\AggregateChanged;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class HotelCreated extends AggregateChanged
{
    public static function raise(
        UuidInterface $hotelId,
        string $name,
        int $rooms
    ) {
        return self::occur(
            $hotelId->toString(),
            [
                'name' => $name,
                'rooms' => $rooms,
            ]
        );
    }

    public function hotelId(): UuidInterface
    {
        return Uuid::fromString($this->aggregateId);
    }

    public function name(): string
    {
        return $this->payload['name'];
    }

    public function rooms(): int
    {
        return $this->payload['rooms'];
    }
}
