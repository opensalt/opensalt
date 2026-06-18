<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Service;

use App\Service\MirrorFramework;
use App\Service\MirrorServer;
use App\Service\SchemaProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Tests for {@see MirrorFramework}.
 */
class MirrorFrameworkTest extends TestCase
{
    protected function tearDown(): void
    {
        SchemaProvider::resetCache();
    }

    private function createMirrorFramework(): MirrorFramework
    {
        $mirrorServer = $this->createMock(MirrorServer::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManager')->willReturn($em);

        return new MirrorFramework($mirrorServer, $managerRegistry, new SchemaProvider());
    }

    private function packageWithEmptyObjectExtensions(): string
    {
        return json_encode([
            'CFDocument' => [
                'uri' => 'https://server/ims/case/v1p0/CFDocuments/d0000000-0000-0000-0000-000000000000',
                'identifier' => 'd0000000-0000-0000-0000-000000000000',
                'lastChangeDateTime' => '2017-09-22T23:13:54+00:00',
                'creator' => 'Test',
                'title' => 'Extensions Test',
                'extensions' => (object) [],
            ],
            'CFItems' => [
                [
                    'uri' => 'https://server/ims/case/v1p0/CFItems/00000000-0000-0000-0000-000000000001',
                    'identifier' => '00000000-0000-0000-0000-000000000001',
                    'lastChangeDateTime' => '2017-09-22T23:15:13+00:00',
                    'fullStatement' => 'Item 1',
                    'extensions' => (object) [],
                ],
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Regression: a CF package with empty-object "extensions": {} must validate.
     *
     * Previously the normalization decoded JSON as associative arrays, which
     * turned {} into [] ; the schema defines Extensions as "type": "object",
     * so validation raised "Object expected, [] received".
     */
    /**
     * Regression: a CF package with empty-object "extensions": {} must validate.
     *
     * Previously the normalization decoded JSON as associative arrays, which
     * turned {} into [] ; the schema defines Extensions as "type": "object",
     * so validation raised "Object expected, [] received".
     */
    public function testValidateAcceptsEmptyObjectExtensions(): void
    {
        $mirror = $this->createMirrorFramework();

        // validate() returns void; it throws RuntimeException on an invalid package.
        // Completing this call without throwing proves the normalization preserved {}.
        $exception = null;
        try {
            $mirror->validate($this->packageWithEmptyObjectExtensions());
        } catch (\Throwable $e) {
            $exception = $e;
        }

        $this->assertNull($exception, 'validate() should not throw for a package with empty-object extensions');
    }

    /**
     * Regression: top-level "extensions" must be stripped during normalization
     * since the CASE v1.1 schema does not define extensions at the package level
     * (additionalProperties: false).
     */
    public function testValidateStripsTopLevelExtensions(): void
    {
        $mirror = $this->createMirrorFramework();

        $pkg = json_encode([
            'CFDocument' => [
                'uri' => 'https://server/ims/case/v1p0/CFDocuments/d0000000-0000-0000-0000-000000000000',
                'identifier' => 'd0000000-0000-0000-0000-000000000000',
                'lastChangeDateTime' => '2017-09-22T23:13:54+00:00',
                'creator' => 'Test',
                'title' => 'Top-level extensions test',
                'extensions' => (object) [],
            ],
            'CFItems' => [
                [
                    'uri' => 'https://server/ims/case/v1p0/CFItems/00000000-0000-0000-0000-000000000001',
                    'identifier' => '00000000-0000-0000-0000-000000000001',
                    'lastChangeDateTime' => '2017-09-22T23:15:13+00:00',
                    'fullStatement' => 'Item 1',
                ],
            ],
            'extensions' => (object) [],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $exception = null;
        try {
            $mirror->validate($pkg);
        } catch (\Throwable $e) {
            $exception = $e;
        }

        $this->assertNull($exception, 'validate() should not throw when top-level extensions is present (it should be stripped)');
    }
}
