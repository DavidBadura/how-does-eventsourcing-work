<?php


namespace App\Domain\Hotel;

use App\Domain\Hotel\Event\GuestCheckedIn;
use App\Domain\Hotel\Event\GuestCheckedOut;
use App\Domain\Hotel\Event\HotelCreated;
use App\Domain\UuidGenerator;
use App\EventSourcing\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class Hotel extends AggregateRoot
{
    private UuidInterface $id;
    private string $name;
    private int $rooms;
    private int $guests;

    private function __construct()
    {
    }

    public static function createHotel(string $name, int $rooms): self
    {
        $id = Uuid::fromString(UuidGenerator::generate());

        $self = new self();
        $self->apply(HotelCreated::raise(
            $id,
            $name,
            $rooms
        ));

        return $self;
    }

    public function guestCheckIn(): void
    {
        if ($this->guests >= $this->rooms) {
            throw new NoRoomAvailableException();
        }

        $this->apply(GuestCheckedIn::raise($this->id));
    }

    public function guestCheckOut(): void
    {
        if ($this->guests <= 0) {
            throw new NoGuestsAvailableException();
        }

        $this->apply(GuestCheckedOut::raise($this->id));
    }

    protected function applyHotelCreated(HotelCreated $event): void
    {
        $this->id = $event->hotelId();
        $this->name = $event->name();
        $this->rooms = $event->rooms();
        $this->guests = 0;
    }

    protected function applyGuestCheckedIn(GuestCheckedIn $event): void
    {
        $this->guests++;
    }

    protected function applyGuestCheckedOut(GuestCheckedOut $event): void
    {
        $this->guests--;
    }

    public function aggregateRootId(): string
    {
        return $this->id->toString();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function rooms(): int
    {
        return $this->rooms;
    }

    public function guests(): int
    {
        return $this->guests;
    }
}
