<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Util;

use App\Util\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Tests for {@see Collection}.
 */
class CollectionTest extends TestCase
{
    public function testRemoveEmptyStringsStripsEmptyStringValues(): void
    {
        $data = ['a' => 'keep', 'b' => '', 'c' => 'also'];

        $result = Collection::removeEmptyStrings($data);

        $this->assertSame(['a' => 'keep', 'c' => 'also'], $result);
    }

    public function testRemoveEmptyStringsLeavesNonEmptyValuesIntact(): void
    {
        $data = ['a' => 'keep', 'zero' => 0, 'false' => false, 'null' => null, 'arr' => [1, 2]];

        $result = Collection::removeEmptyStrings($data);

        $this->assertSame($data, $result);
    }

    public function testRemoveEmptyStringsPreservesEmptyObjects(): void
    {
        $data = ['extensions' => new \stdClass()];

        $result = Collection::removeEmptyStrings($data);

        $this->assertArrayHasKey('extensions', $result);
        // An empty object must remain an empty object so it round-trips to {} (not [])
        $this->assertSame('{}', json_encode($result['extensions']));
    }

    public function testRemoveEmptyStringsRecursesIntoObjectsAndArrays(): void
    {
        $nested = new \stdClass();
        $nested->keep = 'x';
        $nested->drop = '';
        $nested->emptyObj = new \stdClass();

        $data = [
            'items' => [$nested],
            'docExtensions' => new \stdClass(),
            'drop' => '',
        ];

        $result = Collection::removeEmptyStrings($data);

        // Top-level empty string removed
        $this->assertArrayNotHasKey('drop', $result);
        // Empty objects preserved as objects (round-trip to {})
        $this->assertSame('{}', json_encode($result['docExtensions']));
        $this->assertSame('{}', json_encode($result['items'][0]->emptyObj));
        // Nested empty string removed from object
        $this->assertFalse(property_exists($result['items'][0], 'drop'));
        $this->assertSame('x', $result['items'][0]->keep);
    }

    public function testStripUnsupportedExtensionsRemovesTopLevelExtensions(): void
    {
        $data = new \stdClass();
        $data->CFDocument = new \stdClass();
        $data->extensions = new \stdClass();

        $result = Collection::stripUnsupportedExtensions($data);

        $this->assertFalse(property_exists($result, 'extensions'));
        $this->assertTrue(property_exists($result, 'CFDocument'));
    }

    public function testStripUnsupportedExtensionsRemovesExtensionsFromLinkUri(): void
    {
        $linkUri = new \stdClass();
        $linkUri->title = 'Test';
        $linkUri->identifier = 'abc-123';
        $linkUri->uri = 'https://example.com';
        $linkUri->extensions = new \stdClass();

        $data = new \stdClass();
        $data->CFDocument = new \stdClass();
        $data->originNodeURI = $linkUri;

        $result = Collection::stripUnsupportedExtensions($data);

        $this->assertFalse(property_exists($result->originNodeURI, 'extensions'));
        $this->assertSame('Test', $result->originNodeURI->title);
    }

    public function testStripUnsupportedExtensionsPreservesExtensionsOnCfObjects(): void
    {
        $doc = new \stdClass();
        $doc->title = 'Test';
        $doc->extensions = new \stdClass();

        $data = new \stdClass();
        $data->CFDocument = $doc;

        $result = Collection::stripUnsupportedExtensions($data);

        // CFDocument supports extensions per schema — must be preserved
        $this->assertTrue(property_exists($result->CFDocument, 'extensions'));
    }

    public function testStripUnsupportedExtensionsWorksWithArrays(): void
    {
        $data = [
            'CFDocument' => ['title' => 'Test'],
            'extensions' => [],
            'originNodeURI' => [
                'title' => 'Link',
                'identifier' => 'abc',
                'uri' => 'https://example.com',
                'extensions' => ['foo' => 'bar'],
            ],
        ];

        $result = Collection::stripUnsupportedExtensions($data);

        $this->assertArrayNotHasKey('extensions', $result);
        $this->assertArrayNotHasKey('extensions', $result['originNodeURI']);
        $this->assertSame('Test', $result['CFDocument']['title']);
    }
}
