<?php

declare(strict_types=1);

namespace Tests\Unit\Controller\Editor;

use App\Controller\Editor\ItemController;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsDocRepository;
use App\Repository\Framework\LsItemRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ItemControllerTest extends TestCase
{
    public function testUpdateItemClearsHumanCodingSchemeWhenEmptyString(): void
    {
        $lsItem = new LsItem();
        $lsItem->setFullStatement('Test');
        $lsItem->setHumanCodingScheme('ABC');

        $controller = $this->createItemController();

        $request = new Request(
            content: json_encode(['humanCodingScheme' => ''], JSON_THROW_ON_ERROR)
        );

        $response = $controller->updateItem($request, $lsItem);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNull($lsItem->getHumanCodingScheme());
    }

    public function testUpdateItemClearsHumanCodingSchemeWhenNull(): void
    {
        $lsItem = new LsItem();
        $lsItem->setFullStatement('Test');
        $lsItem->setHumanCodingScheme('ABC');

        $controller = $this->createItemController();

        $request = new Request(
            content: json_encode(['humanCodingScheme' => null], JSON_THROW_ON_ERROR)
        );

        $controller->updateItem($request, $lsItem);

        $this->assertNull($lsItem->getHumanCodingScheme());
    }

    public function testUpdateItemClearsAbbreviatedStatementWhenEmptyString(): void
    {
        $lsItem = new LsItem();
        $lsItem->setFullStatement('Test');
        $lsItem->setAbbreviatedStatement('Abbr');

        $controller = $this->createItemController();

        $request = new Request(
            content: json_encode(['abbreviatedStatement' => ''], JSON_THROW_ON_ERROR)
        );

        $controller->updateItem($request, $lsItem);

        $this->assertNull($lsItem->getAbbreviatedStatement());
    }

    public function testUpdateItemClearsNotesWhenEmptyString(): void
    {
        $lsItem = new LsItem();
        $lsItem->setFullStatement('Test');
        $lsItem->setNotes('Some notes');

        $controller = $this->createItemController();

        $request = new Request(
            content: json_encode(['notes' => ''], JSON_THROW_ON_ERROR)
        );

        $controller->updateItem($request, $lsItem);

        $this->assertNull($lsItem->getNotes());
    }

    public function testUpdateItemClearsLanguageWhenEmptyString(): void
    {
        $lsItem = new LsItem();
        $lsItem->setFullStatement('Test');
        $lsItem->setLanguage('en');

        $controller = $this->createItemController();

        $request = new Request(
            content: json_encode(['language' => ''], JSON_THROW_ON_ERROR)
        );

        $controller->updateItem($request, $lsItem);

        $this->assertNull($lsItem->getLanguage());
    }

    public function testUpdateItemDoesNotClearFieldsWhenAbsent(): void
    {
        $lsItem = new LsItem();
        $lsItem->setFullStatement('Test');
        $lsItem->setHumanCodingScheme('ABC');
        $lsItem->setNotes('Keep me');

        $controller = $this->createItemController();

        $request = new Request(
            content: json_encode(['fullStatement' => 'Updated'], JSON_THROW_ON_ERROR)
        );

        $controller->updateItem($request, $lsItem);

        $this->assertSame('ABC', $lsItem->getHumanCodingScheme());
        $this->assertSame('Keep me', $lsItem->getNotes());
    }

    private function createItemController(): ItemController
    {
        $controller = new ItemController(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(HtmlSanitizerInterface::class),
            $this->createMock(LsDocRepository::class),
            $this->createMock(LsItemRepository::class),
            $this->createMock(LsAssociationRepository::class),
            $this->createMock(Security::class),
        );
        $controller->setDispatcher($this->createMock(EventDispatcherInterface::class));

        return $controller;
    }
}
