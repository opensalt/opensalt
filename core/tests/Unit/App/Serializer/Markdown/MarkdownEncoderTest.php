<?php

declare(strict_types=1);

namespace Tests\Unit\App\Serializer\Markdown;

use App\Serializer\Markdown\MarkdownEncoder;

class MarkdownEncoderTest extends \Codeception\Test\Unit
{
    public function testAssemblesFrontMatterAndBody(): void
    {
        $e = new MarkdownEncoder();
        $out = $e->encode(['front_matter' => ['type' => 'CFItem'], 'body' => '# Title'], 'md');

        self::assertSame("---\ntype: CFItem\n---\n\n# Title", $out);
    }

    public function testBodyOnlyWhenNoFrontMatter(): void
    {
        $e = new MarkdownEncoder();
        self::assertSame('# Title', $e->encode(['front_matter' => [], 'body' => '# Title'], 'md'));
        self::assertSame('plain', $e->encode('plain', 'md'));
    }

    public function testSupportsOnlyMd(): void
    {
        $e = new MarkdownEncoder();
        self::assertTrue($e->supportsEncoding('md'));
        self::assertFalse($e->supportsEncoding('json'));
    }
}
