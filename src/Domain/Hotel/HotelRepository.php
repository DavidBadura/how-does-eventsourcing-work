<?php declare(strict_types=1);

namespace App\Domain\Hotel;

use App\EventSourcing\AggregateNotFoundException;
use App\EventSourcing\Repository;
use Ramsey\Uuid\UuidInterface;

class HotelRepository
{
    /**
     * @var Repository
     */
    private $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function store(Hotel $battle): void
    {
        $this->repository->save($battle);
    }

    public function get(UuidInterface $id): Hotel
    {
        try {
            /** @var Hotel $hotel */
            $hotel = $this->repository->load($id->toString());
        } catch (AggregateNotFoundException $e) {
            throw new HotelNotFoundException($id);
        }

        return $hotel;
    }
}
