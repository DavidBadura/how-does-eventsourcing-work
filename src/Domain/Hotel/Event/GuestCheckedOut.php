<?php

namespace App\Domain\Hotel\Event;

use App\EventSourcing\AggregateChanged;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class GuestCheckedOut extends AggregateChanged
{
    public static function raise(
        UuidInterface $hotelId
    ) {
        return self::occur(
            $hotelId->toString()
        );
    }

    public function hotelId(): UuidInterface
    {
        return Uuid::fromString($this->aggregateId);
    }
}

