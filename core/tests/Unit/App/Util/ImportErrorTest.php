<?php

declare(strict_types=1);

namespace Tests\Unit\App\Util;

use App\Exception\ImportFailedException;
use App\Util\ImportError;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\DriverException as DbalDriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;
use Swaggest\JsonSchema\Exception as SchemaException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ImportErrorTest extends TestCase
{
    public function testSchemaErrorIsStrippedOfDataDump(): void
    {
        $throwable = new SchemaException(
            'Required property missing: uri, data: {"identifier":"abc","title":"Test"} at #->properties'
        );

        self::assertSame('Required property missing: uri at #->properties', ImportError::message($throwable));
    }

    public function testMultilineDataDumpIsStripped(): void
    {
        $throwable = new SchemaException(
            "Required property missing: title, data: {\n  \"a\": 1,\n  \"b\": 2\n} at #->properties"
        );

        self::assertSame('Required property missing: title at #->properties', ImportError::message($throwable));
    }

    public function testJsonExceptionGetsFriendlyPrefix(): void
    {
        $throwable = new \JsonException('Syntax error');

        self::assertSame('The file is not valid JSON: Syntax error', ImportError::message($throwable));
    }

    public function testImportFailedExceptionMessageIsPassedThrough(): void
    {
        $throwable = new ImportFailedException('The current user cannot update this framework');

        self::assertSame('The current user cannot update this framework', ImportError::message($throwable));
    }

    public function testDuplicateConstraintGetsFriendlyMessage(): void
    {
        $driver = new class('Duplicate entry') extends \Exception implements DriverException {
            public function getSQLState(): string
            {
                return '23000';
            }
        };
        $throwable = new UniqueConstraintViolationException($driver, null);

        self::assertSame(ImportError::DUPLICATE, ImportError::message($throwable));
    }

    public function testTooLongValueGetsFriendlyMessage(): void
    {
        $driver = new class("Data too long for column 'title'") extends \Exception implements DriverException {
            public function getSQLState(): string
            {
                return '22001';
            }
        };
        $throwable = new DbalDriverException($driver, null);

        self::assertSame(ImportError::DATA_TOO_LONG, ImportError::message($throwable));
    }

    public function testOtherDatabaseErrorsDoNotLeakSql(): void
    {
        $driver = new class('Connection refused') extends \Exception implements DriverException {
            public function getSQLState(): ?string
            {
                return null;
            }
        };
        $throwable = new DbalDriverException($driver, null);

        self::assertSame(ImportError::GENERIC, ImportError::message($throwable));
    }

    public function testSerializerMismatchGetsStructuredMessage(): void
    {
        $throwable = NotNormalizableValueException::createForUnexpectedDataType(
            'The type of the key "CFItems" must be "array", "string" given.',
            'oops',
            ['array'],
            'CFItems',
        );

        self::assertSame(
            'The file does not match the CASE structure: The type of the key "CFItems" must be "array", "string" given.',
            ImportError::message($throwable),
        );
    }

    public function testValidationFailureMessageIsPassedThrough(): void
    {
        $violation = new ConstraintViolation('This value should not be null.', null, [], new \stdClass(), 'caseJson', null);
        $throwable = new ValidationFailedException(new \stdClass(), new ConstraintViolationList([$violation]));

        self::assertSame('Object(stdClass).caseJson: This value should not be null.', ImportError::message($throwable));
    }

    public function testRuntimeExceptionsDoNotLeakInternalMessages(): void
    {
        $throwable = new \RuntimeException('An exception occurred while executing a query: SQLSTATE[23000]: Duplicate entry');

        self::assertSame(ImportError::GENERIC, ImportError::message($throwable));
    }

    public function testOtherExceptionsGetTheGenericMessage(): void
    {
        $throwable = new \LogicException('some internal detail');

        self::assertSame(ImportError::GENERIC, ImportError::message($throwable));
    }
}
