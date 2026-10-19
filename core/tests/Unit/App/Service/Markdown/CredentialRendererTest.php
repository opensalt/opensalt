<?php

declare(strict_types=1);

namespace Tests\Unit\App\Service\Markdown;

use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsItemRepository;
use App\Service\Markdown\CredentialAssociationsResolver;
use App\Service\Markdown\HtmlToMarkdownConverter;
use App\Service\Markdown\Renderer\CredentialRenderer;
use App\Service\UriGenerator;
use Symfony\Component\Routing\RouterInterface;

class CredentialRendererTest extends \Codeception\Test\Unit
{
    public function testRendersCriteriaAlignmentsAndRawOb3(): void
    {
        $item = new LsItem();
        $item->setLsDoc(new \App\Entity\Framework\LsDoc());
        $item->setDiscriminator(LsItemKind::Credential->value);
        $item->setFullStatement('<p>CNA description</p>');
        $ob3 = json_encode([
            'type' => ['Achievement'],
            'achievementType' => 'Certification',
            'name' => 'CNA',
            'description' => '<p>CNA description</p>',
            'humanCode' => 'CNA-100',
            'inLanguage' => 'en',
            'tag' => ['healthcare'],
            'criteria' => ['narrative' => '<p>Must pass exam</p>'],
        ], JSON_THROW_ON_ERROR);
        $item->setExtensionProperty('ob3', $ob3);

        $r = $this->makeRenderer();
        self::assertTrue($r->supports($item));

        $out = $r->render($item);
        self::assertStringContainsString('## Statement', $out);
        self::assertStringContainsString('CNA description', $out);
        self::assertStringContainsString('## Properties', $out);
        self::assertStringContainsString('Certification', $out);
        self::assertStringContainsString('## Criteria', $out);
        self::assertStringContainsString('Must pass exam', $out);
        self::assertStringContainsString('### Raw credential data', $out);
        self::assertStringContainsString('"achievementType"', $out);
    }

    public function testPackageModeOmitsRawOb3(): void
    {
        $item = new LsItem();
        $item->setLsDoc(new \App\Entity\Framework\LsDoc());
        $item->setDiscriminator(LsItemKind::Credential->value);
        $item->setExtensionProperty('ob3', json_encode(['name' => 'X'], JSON_THROW_ON_ERROR));

        $out = $this->makeRenderer()->render($item, ['markdown-package' => true]);
        self::assertStringNotContainsString('Raw credential data', $out);
    }

    private function makeRenderer(): CredentialRenderer
    {
        $assocRepo = $this->createMock(LsAssociationRepository::class);
        $assocRepo->method('findAllAssociationsForAsSplitArray')->willReturn(['associations' => [], 'inverseAssociations' => []]);
        $resolver = new CredentialAssociationsResolver($assocRepo, $this->createMock(LsItemRepository::class));

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn (string $name, array $params = []) => 'https://localhost/uri/'.($params['uri'] ?? '')
        );

        return new CredentialRenderer(
            new HtmlToMarkdownConverter(),
            $resolver,
            new UriGenerator($router)
        );
    }
}
