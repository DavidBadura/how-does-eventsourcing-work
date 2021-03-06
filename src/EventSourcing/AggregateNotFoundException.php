<?php declare(strict_types=1);

namespace App\EventSourcing;

class AggregateNotFoundException extends \RuntimeException
{
    public function __construct(string $aggregateClass, string $id)
    {
        parent::__construct(sprintf('aggregate "%s::%s" not found', $aggregateClass, $id));
    }
}
