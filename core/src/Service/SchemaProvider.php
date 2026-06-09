<?php

declare(strict_types=1);

namespace App\Service;

use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;

class SchemaProvider
{
    private static ?SchemaContract $caseV1p1Schema = null;

    public function getCaseV1p1Schema(): SchemaContract
    {
        if (null === self::$caseV1p1Schema) {
            $schemaPath = __DIR__.'/../../config/schema/case-v1p1-cfpackage-schema.json';
            self::$caseV1p1Schema = Schema::import(json5_decode(file_get_contents($schemaPath) ?: ''));
        }

        return self::$caseV1p1Schema;
    }

    public static function resetCache(): void
    {
        self::$caseV1p1Schema = null;
    }
}
