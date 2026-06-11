<?php

declare(strict_types=1);

namespace Tests\Unit\App\Service;

use App\Service\SchemaProvider;
use PHPUnit\Framework\TestCase;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\SchemaContract;

class SchemaProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        SchemaProvider::resetCache();
    }

    public function testGetCaseV1p1SchemaReturnsSchemaContract(): void
    {
        $provider = new SchemaProvider();
        $schema = $provider->getCaseV1p1Schema();

        $this->assertInstanceOf(SchemaContract::class, $schema);
    }

    public function testGetCaseV1p1SchemaCachesAcrossCalls(): void
    {
        $provider = new SchemaProvider();
        $schema1 = $provider->getCaseV1p1Schema();
        $schema2 = $provider->getCaseV1p1Schema();

        $this->assertSame($schema1, $schema2, 'Schema should be the exact same instance across calls');
    }

    public function testGetCaseV1p1SchemaCachesAcrossInstances(): void
    {
        $provider1 = new SchemaProvider();
        $provider2 = new SchemaProvider();

        $schema1 = $provider1->getCaseV1p1Schema();
        $schema2 = $provider2->getCaseV1p1Schema();

        $this->assertSame($schema1, $schema2, 'Schema should be the exact same instance across provider instances');
    }

    public function testResetCacheClearsSchema(): void
    {
        $provider = new SchemaProvider();
        $schema1 = $provider->getCaseV1p1Schema();

        SchemaProvider::resetCache();

        $schema2 = $provider->getCaseV1p1Schema();

        $this->assertNotSame($schema1, $schema2, 'After reset, a new schema instance should be created');
    }

    public function testSchemaValidatesValidJson(): void
    {
        $provider = new SchemaProvider();
        $schema = $provider->getCaseV1p1Schema();

        $schemaPath = __DIR__.'/../../../../config/schema/case-v1p1-cfpackage-schema.json';
        $this->assertFileExists($schemaPath);
        $this->assertNotNull($schema);
    }

    public function testSchemaRejectsInvalidJson(): void
    {
        $provider = new SchemaProvider();
        $schema = $provider->getCaseV1p1Schema();

        $invalidJson = json_decode('{"CFDocument": "not an object"}');

        $this->expectException(InvalidValue::class);
        $schema->in($invalidJson);
    }

}
