<?php

declare(strict_types=1);

namespace App\Articulations\Service;

final class CourseCodeNormalizer
{
    public function normalize(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', '', $value) ?? '';
        $value = rtrim($value, '.');

        return preg_replace_callback(
            '/\d+/',
            static function (array $matches): string {
                $digits = $matches[0];
                $stripped = ltrim($digits, '0');

                return '' === $stripped ? '0' : $stripped;
            },
            $value,
        ) ?? $value;
    }
}
