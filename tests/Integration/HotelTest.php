<?php

namespace App\Tests\Integration;

use App\Domain\Hotel\Hotel;

class HotelTest extends IntegrationTestCase
{
    public function testSaveHotel(): void
    {
        $repository = self::hotelRepository();




        $hotel = Hotel::createHotel('test', 123);
        $hotel->guestCheckIn();
        $hotel->guestCheckIn();
        $hotel->guestCheckIn();

        $hotel->guestCheckOut();


        $repository->store($hotel);

        self::assertTrue(true);
    }
}
