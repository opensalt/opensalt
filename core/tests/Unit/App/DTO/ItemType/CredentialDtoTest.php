<?php

namespace Tests\Unit\App\DTO\ItemType;

use App\DTO\ItemType\CredentialDto;
use App\Entity\Framework\LsItem;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class CredentialDtoTest extends \Codeception\Test\Unit
{
    private function createSanitizer(): HtmlSanitizerInterface
    {
        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        // Pass-through so assertions can compare against the raw description.
        $sanitizer->method('sanitizeFor')->willReturnArgument(1);

        return $sanitizer;
    }

    public function testApplyToItemCopiesDescriptionToFullStatement(): void
    {
        $credential = json_encode([
            'name' => 'My Badge',
            'description' => 'A great badge',
            'criteria' => ['narrative' => 'Do the thing'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $item = new LsItem();
        new CredentialDto($credential)->applyToItem($item, $this->createSanitizer());

        $this->assertSame('A great badge', $item->getFullStatement());
        $this->assertSame('My Badge', $item->getAbbreviatedStatement());
    }

    public function testApplyToItemHandlesDoubleEncodedCredential(): void
    {
        // The credential value is JSON-encoded twice (as produced when the widget
        // emits an already-stringified string that is then stringified again).
        $inner = json_encode([
            'name' => 'My Badge',
            'description' => 'A great badge',
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $doubleEncoded = json_encode($inner, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $item = new LsItem();
        new CredentialDto($doubleEncoded)->applyToItem($item, $this->createSanitizer());

        $this->assertSame('A great badge', $item->getFullStatement());
        $this->assertSame('My Badge', $item->getAbbreviatedStatement());
    }

    public function testApplyToItemDoesNotThrowWhenDescriptionIsMissing(): void
    {
        // Without the null-safe handling this raised a TypeError (null passed to
        // the non-nullable setFullStatement) and produced a 500 error.
        $credential = json_encode(['name' => 'No description'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $item = new LsItem();

        new CredentialDto($credential)->applyToItem($item, $this->createSanitizer());

        $this->assertSame('', $item->getFullStatement());
        $this->assertSame('No description', $item->getAbbreviatedStatement());
    }

    public function testApplyToItemHandlesGarbageCredentialGracefully(): void
    {
        $item = new LsItem();

        new CredentialDto('not-json-at-all')->applyToItem($item, $this->createSanitizer());

        $this->assertSame('', $item->getFullStatement());
    }
}
