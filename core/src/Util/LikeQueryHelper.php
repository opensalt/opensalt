<?php

declare(strict_types=1);

namespace App\Util;

final class LikeQueryHelper
{
    private function __construct()
    {
    }

    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    public static function contains(string $value): string
    {
        return '%'.self::escapeLike($value).'%';
    }

    public static function containsLower(string $value): string
    {
        return '%'.self::escapeLike(mb_strtolower($value)).'%';
    }
}
