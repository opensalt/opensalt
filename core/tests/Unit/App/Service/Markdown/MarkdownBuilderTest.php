<?php

declare(strict_types=1);

namespace Tests\Unit\App\Service\Markdown;

use App\Service\Markdown\MarkdownBuilder;
use Symfony\Component\Yaml\Yaml;

class MarkdownBuilderTest extends \Codeception\Test\Unit
{
    public function testFrontMatterRoundsTripThroughYamlParser(): void
    {
        $out = MarkdownBuilder::frontMatter([
            'type' => 'CFItem',
            'extensions' => ['ceterms:subjectWebpage' => 'https://example.org/x'],
        ]);

        self::assertStringStartsWith("---\n", $out);
        self::assertStringEndsWith("\n---\n", $out);
        $parsed = Yaml::parse(substr($out, 4, -5));
        self::assertSame('CFItem', $parsed['type']);
        self::assertSame('https://example.org/x', $parsed['extensions']['ceterms:subjectWebpage']);
    }

    public function testFrontMatterEmptyReturnsEmptyString(): void
    {
        self::assertSame('', MarkdownBuilder::frontMatter([]));
    }

    public function testTableEscapesPipesAndNewlines(): void
    {
        $out = MarkdownBuilder::table(
            ['Relationship', 'Target'],
            [['isChildOf', "a | b\nc"]]
        );

        self::assertSame(
            "| Relationship | Target |\n|---|---|\n| isChildOf | a \\| b<br>c |",
            $out
        );
    }

    public function testJoinBlocksSkipsEmpty(): void
    {
        self::assertSame("a\n\nb", MarkdownBuilder::joinBlocks(['a', '', 'b']));
    }
}
