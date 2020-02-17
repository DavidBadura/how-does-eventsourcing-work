<?php declare(strict_types=1);

namespace App\Domain;

use Ramsey\Uuid\Uuid;

class UuidGenerator
{
    /**
     * @var int
     */
    private static $counter = 0;

    /**
     * @var bool
     */
    private static $dummy = false;

    public static function check(string $uuid): bool
    {
        if (!self::$dummy) {
            return Uuid::isValid($uuid);
        }

        return true;
    }

    public static function generate(): string
    {
        if (!self::$dummy) {
            return Uuid::uuid4()->toString();
        }

        self::$counter++;

        return sprintf('10000000-0000-0000-0000-%s', str_pad((string)self::$counter, 12, '0', STR_PAD_LEFT));
    }

    public static function dummy(bool $bool): void
    {
        self::$dummy = $bool;
        self::$counter = 0;
    }
}
