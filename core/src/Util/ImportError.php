<?php

declare(strict_types=1);

namespace App\Util;

use App\Exception\ImportFailedException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Swaggest\JsonSchema\Exception as SchemaException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Builds user-facing messages for framework import failures. File problems
 * (invalid JSON, CASE schema violations, duplicate content, oversized values)
 * are described specifically; anything else falls back to a generic message
 * so internal and database errors are never exposed.
 */
final class ImportError
{
    public const GENERIC = 'Error while importing the file.';
    public const DUPLICATE = 'A framework with the same identifier or URI already exists, or the file conflicts with existing content. Rename the framework or change its identifiers and try again.';
    public const DATA_TOO_LONG = 'A value in the file is too long or not valid for one of the framework fields. Shorten it or check it against the CASE format and try again.';

    public static function message(\Throwable $throwable): string
    {
        if ($throwable instanceof SchemaException) {
            // The schema library embeds the entire data object in the message;
            // keep the actionable part (what is wrong and where it failed).
            return trim((string) preg_replace('/, data: \{.*\}/s', '', $throwable->getMessage()));
        }

        if ($throwable instanceof \JsonException) {
            return 'The file is not valid JSON: '.$throwable->getMessage();
        }

        if ($throwable instanceof UniqueConstraintViolationException) {
            return self::DUPLICATE;
        }

        // SQLSTATE class 22 = data exceptions, e.g. a value longer than the
        // database column ("data too long for column ...").
        if ($throwable instanceof DriverException
            && null !== $throwable->getSQLState()
            && str_starts_with($throwable->getSQLState(), '22')
        ) {
            return self::DATA_TOO_LONG;
        }

        if ($throwable instanceof NotNormalizableValueException) {
            return 'The file does not match the CASE structure: '.$throwable->getMessage();
        }

        if ($throwable instanceof ValidationFailedException) {
            // Violation lists render multi-line; collapse to one readable line.
            $message = trim($throwable->getMessage());

            return '' === $message ? self::GENERIC : preg_replace('/\s+/', ' ', $message);
        }

        if ($throwable instanceof ImportFailedException) {
            return $throwable->getMessage();
        }

        return self::GENERIC;
    }
}
