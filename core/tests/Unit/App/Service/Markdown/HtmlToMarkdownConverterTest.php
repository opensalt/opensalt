<?php

declare(strict_types=1);

namespace Tests\Unit\App\Service\Markdown;

use App\Service\Markdown\HtmlToMarkdownConverter;

class HtmlToMarkdownConverterTest extends \Codeception\Test\Unit
{
    public function testConvertsParagraphAndList(): void
    {
        $c = new HtmlToMarkdownConverter();
        $md = $c->convert('<p>One</p><ul><li>A</li><li>B</li></ul>');

        self::assertStringContainsString('One', $md);
        self::assertStringContainsString('- A', $md);
        self::assertStringContainsString('- B', $md);
    }

    public function testConvertsLink(): void
    {
        $c = new HtmlToMarkdownConverter();
        self::assertStringContainsString('[Example](https://example.com)', $c->convert('<a href="https://example.com">Example</a>'));
    }

    public function testNullAndEmptyGiveEmpty(): void
    {
        $c = new HtmlToMarkdownConverter();
        self::assertSame('', $c->convert(null));
        self::assertSame('', $c->convert(''));
    }

    public function testMarkdownContentPassesThroughUnescaped(): void
    {
        $c = new HtmlToMarkdownConverter();
        $md = $c->convert("Intro paragraph.\n\n### Content\n\n- **bold** item\n  - nested");

        self::assertStringContainsString("Intro paragraph.\n\n### Content", $md);
        self::assertStringContainsString('- **bold** item', $md);
        self::assertStringNotContainsString('\\*', $md);
    }

    public function testHtmlStillConverts(): void
    {
        $c = new HtmlToMarkdownConverter();
        $md = $c->convert('<p>Intro.</p><h3>Content</h3><ul><li><strong>bold</strong> item</li></ul>');

        self::assertStringContainsString('### Content', $md);
        self::assertStringContainsString('**bold** item', $md);
    }
}
