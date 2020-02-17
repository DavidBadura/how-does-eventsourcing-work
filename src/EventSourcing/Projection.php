<?php declare(strict_types=1);

namespace App\EventSourcing;

use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

abstract class Projection implements MessageSubscriberInterface
{
    abstract public function drop(): void;
}
