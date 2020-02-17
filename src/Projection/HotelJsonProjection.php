<?php declare(strict_types=1);

namespace App\Projection;

use App\Domain\Hotel\Event\GuestCheckedIn;
use App\Domain\Hotel\Event\GuestCheckedOut;
use App\Domain\Hotel\Event\HotelCreated;
use App\EventSourcing\Projection;
use Ramsey\Uuid\UuidInterface;

class HotelJsonProjection extends Projection
{
    private string $directory;

    public function __construct()
    {
        $this->directory = __DIR__ . '/../../public/api/hotel';
    }

    public static function getHandledMessages(): iterable
    {
        yield HotelCreated::class => 'applyHotelCreated';
        yield GuestCheckedIn::class => 'applyGuestCheckedIn';
        yield GuestCheckedOut::class => 'applyGuestCheckedOut';
    }

    public function applyHotelCreated(HotelCreated $event): void
    {
        $data = [
            'id' => $event->hotelId()->toString(),
            'name' => $event->name(),
            'rooms' => $event->rooms(),
            'guests' => 0
        ];

        $this->write($event->hotelId(), $data);
    }

    public function applyGuestCheckedIn(GuestCheckedIn $event): void
    {
        $data = $this->read($event->hotelId());

        $data['guests']++;

        $this->write($event->hotelId(), $data);

    }

    public function applyGuestCheckedOut(GuestCheckedOut $event): void
    {
        $data = $this->read($event->hotelId());

        $data['guests']--;

        $this->write($event->hotelId(), $data);
    }

    private function read(UuidInterface $uuid): array
    {
        return json_decode(file_get_contents($this->path($uuid)), true);
    }

    private function write(UuidInterface $uuid, array $data): void
    {
        file_put_contents($this->path($uuid), json_encode($data));
    }

    private function path(UuidInterface $uuid): string
    {
        return $this->directory . '/' . $uuid->toString() . '.json';
    }

    public function drop(): void
    {
        // TODO: Implement drop() method.
    }
}
