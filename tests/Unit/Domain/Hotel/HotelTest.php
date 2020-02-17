<?php

namespace App\Tests\Unit\Domain\Hotel;

use App\Domain\Hotel\Event\GuestCheckedIn;
use App\Domain\Hotel\Event\HotelCreated;
use App\Domain\Hotel\Hotel;
use App\Domain\Hotel\NoGuestsAvailableException;
use App\Domain\Hotel\NoRoomAvailableException;
use App\EventSourcing\AggregateRoot;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class HotelTest extends TestCase
{
    public function testCreateHotel(): void
    {
        $hotel = Hotel::createHotel('Foo', 10);

        self::assertEquals('Foo', $hotel->name());
        self::assertEquals(10, $hotel->rooms());
        self::assertEquals(0, $hotel->guests());
    }

    public function testCheckedIn(): void
    {
        $hotel = Hotel::createHotel('Foo', 10);
        $hotel->guestCheckIn();
        $hotel->guestCheckIn();
        $hotel->guestCheckIn();

        self::assertEquals('Foo', $hotel->name());
        self::assertEquals(10, $hotel->rooms());
        self::assertEquals(3, $hotel->guests());
    }

    public function testCheckedOut(): void
    {
        $hotel = Hotel::createHotel('Foo', 10);
        $hotel->guestCheckIn();
        $hotel->guestCheckIn();
        $hotel->guestCheckIn();

        self::assertEquals(3, $hotel->guests());

        $hotel->guestCheckOut();

        self::assertEquals(2, $hotel->guests());
    }

    public function testNoRoomAvailable(): void
    {
        $this->expectException(NoRoomAvailableException::class);

        $hotel = Hotel::createHotel('Foo', 2);
        $hotel->guestCheckIn();
        $hotel->guestCheckIn();
        $hotel->guestCheckIn();
    }

    public function testNoGuestsAvailable(): void
    {
        $this->expectException(NoGuestsAvailableException::class);

        $hotel = Hotel::createHotel('Foo', 2);
        $hotel->guestCheckOut();
    }

    public function testEvents(): void
    {
        $hotel = Hotel::createHotel('Foo', 2);
        $hotel->guestCheckIn();
        $hotel->guestCheckIn();
        $hotel->guestCheckOut();
        $hotel->guestCheckIn();
        $hotel->guestCheckOut();
        $hotel->guestCheckOut();

        $events = $hotel->releaseEvents();

        dump($events);

        self::assertCount(7, $events);
    }

    public function testRebuild(): void
    {
        $id = Uuid::uuid4();

        $events = [
            HotelCreated::raise(
                $id,
                'Foo',
                42
            ),
            GuestCheckedIn::raise($id),
            GuestCheckedIn::raise($id),
            GuestCheckedIn::raise($id),
            GuestCheckedIn::raise($id),
        ];

        $reflaction = new \ReflectionClass(Hotel::class);

        /** @var AggregateRoot $hotel */
        $hotel = $reflaction->newInstanceWithoutConstructor();
        $hotel->initializeState($events);

        self::assertEquals('Foo', $hotel->name());
        self::assertEquals(42, $hotel->rooms());
        self::assertEquals(4, $hotel->guests());
    }
}
