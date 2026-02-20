<?php

namespace App\Services\AcseeAdmin;

use Illuminate\Support\Str;

class CorrelationIdService
{
    private static ?string $currentId = null;

    public static function get(): string
    {
        if (self::$currentId === null) {
            self::$currentId = Str::uuid()->toString();
        }
        return self::$currentId;
    }

    public static function set(string $id): void
    {
        self::$currentId = $id;
    }

    public static function reset(): void
    {
        self::$currentId = null;
    }
}
