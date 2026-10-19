<?php

declare(strict_types=1);

namespace Tests\Unit\App\Service\Markdown;

use App\DTO\ItemType\CourseDto;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Service\Markdown\HtmlToMarkdownConverter;
use App\Service\Markdown\Renderer\AssessmentRenderer;
use App\Service\Markdown\Renderer\CourseRenderer;
use App\Service\Markdown\Renderer\IdentifierRenderer;
use App\Service\Markdown\Renderer\JobRenderer;
use App\Service\Markdown\Renderer\OrganizationRenderer;
use App\Service\Markdown\Renderer\PublicKeyRenderer;

class KindRenderersTest extends \Codeception\Test\Unit
{
    public function testCourseRendererShowsCodedNotationAndWebpage(): void
    {
        $item = new LsItem();
        $item->setDiscriminator(LsItemKind::Course->value);
        $item->setFullStatement('Intro to CS');
        $item->setHumanCodingScheme('CS-101');
        $item->setExtensionProperty(CourseDto::WEBPAGE_KEY, 'https://example.edu/cs101');

        $r = new CourseRenderer(new HtmlToMarkdownConverter());
        self::assertTrue($r->supports($item));

        $out = $r->render($item);
        self::assertStringContainsString('## Properties', $out);
        self::assertStringContainsString('CS-101', $out);
        self::assertStringContainsString('https://example.edu/cs101', $out);
        self::assertStringContainsString('Intro to CS', $out);
    }

    public function testPublicKeyRendererEmitsJsonFence(): void
    {
        $item = new LsItem();
        $item->setDiscriminator(LsItemKind::PublicKey->value);
        $item->setAbbreviatedStatement('test-kid');
        $item->setFullStatement('{"kty":"OKP","kid":"test-kid"}');

        $r = new PublicKeyRenderer(new HtmlToMarkdownConverter());
        self::assertTrue($r->supports($item));
        $out = $r->render($item);
        self::assertStringContainsString('```json', $out);
        self::assertStringContainsString('"kty":"OKP"', $out);
        self::assertStringContainsString('test-kid', $out);
    }

    public function testIdentifierRendererShowsIdentifierAsCode(): void
    {
        $item = new LsItem();
        $item->setDiscriminator(LsItemKind::Identifier->value);
        $item->setAbbreviatedStatement('urn:example:123');

        $r = new IdentifierRenderer(new HtmlToMarkdownConverter());
        $out = $r->render($item);
        self::assertStringContainsString('`urn:example:123`', $out);
    }

    public function testSupportsBoundaries(): void
    {
        $plain = new LsItem();
        self::assertFalse((new CourseRenderer(new HtmlToMarkdownConverter()))->supports($plain));
        self::assertFalse((new JobRenderer(new HtmlToMarkdownConverter()))->supports($plain));
        self::assertFalse((new AssessmentRenderer(new HtmlToMarkdownConverter()))->supports($plain));
        self::assertFalse((new OrganizationRenderer(new HtmlToMarkdownConverter()))->supports($plain));
        self::assertFalse((new IdentifierRenderer(new HtmlToMarkdownConverter()))->supports($plain));
        self::assertFalse((new PublicKeyRenderer(new HtmlToMarkdownConverter()))->supports($plain));
    }
}
