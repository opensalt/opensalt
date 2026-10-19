<?php

declare(strict_types=1);

namespace Tests\Unit\App\Service\Markdown;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Service\Api1Uris;
use App\Service\Markdown\AssociationRowsCollector;
use App\Service\UriGenerator;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AssociationRowsCollectorTest extends \Codeception\Test\Unit
{
    public function testCollectsOutgoingRowWithKindAndLink(): void
    {
        $doc = new LsDoc();
        $doc->setTitle('Test framework');

        $item = new LsItem();
        $item->setLsDoc($doc);

        $target = new LsItem();
        $target->setLsDoc($doc);
        $target->setAbbreviatedStatement('Target item');
        $target->setHumanCodingScheme('T-100');

        $assoc = new LsAssociation();
        $assoc->setLsDoc($doc);
        $assoc->setType(LsAssociation::RELATED_TO);
        $assoc->setOrigin($item);
        $assoc->setDestination($target);

        $item->addAssociation($assoc);

        $collector = $this->makeCollector();

        $rows = $collector->collect($item);

        self::assertCount(1, $rows['outgoing']);
        $row = $rows['outgoing'][0];
        self::assertSame('isRelatedTo', $row['relationship']);
        self::assertSame('item', $row['kind']);
        self::assertSame('1', $row['local']);
        self::assertStringEndsWith('/uri/'.$target->getIdentifier(), $row['uri']);
        self::assertStringContainsString('Target item', $row['title']);
        self::assertSame('T-100', $row['code']);
        self::assertSame('Test framework', $row['framework']);
        self::assertStringContainsString('/uri/', (string) $row['frameworkUri']);
    }

    public function testIncomingChildOfBecomesIsParentOf(): void
    {
        $doc = new LsDoc();

        $parent = new LsItem();
        $parent->setLsDoc($doc);

        $child = new LsItem();
        $child->setLsDoc($doc);

        $assoc = new LsAssociation();
        $assoc->setLsDoc($doc);
        $assoc->setType(LsAssociation::CHILD_OF);
        $assoc->setOrigin($child);
        $assoc->setDestination($parent);
        $child->addAssociation($assoc);
        $parent->addInverseAssociation($assoc);

        $collector = $this->makeCollector();

        $rows = $collector->collect($parent);

        self::assertCount(1, $rows['incoming']);
        self::assertSame('isParentOf', $rows['incoming'][0]['relationship']);
    }

    public function testExternalDestinationIsNotLocal(): void
    {
        $doc = new LsDoc();
        $item = new LsItem();
        $item->setLsDoc($doc);

        $assoc = new LsAssociation();
        $assoc->setLsDoc($doc);
        $assoc->setType(LsAssociation::EXEMPLAR);
        $assoc->setOrigin($item);
        $assoc->setDestination('https://example.com/resource');
        $item->addAssociation($assoc);

        $collector = $this->makeCollector();
        $rows = $collector->collect($item);

        self::assertSame('external', $rows['outgoing'][0]['kind']);
        self::assertSame('', $rows['outgoing'][0]['local']);
        self::assertSame('https://example.com/resource', $rows['outgoing'][0]['uri']);
    }

    public function testUnresolvableIdentifierBecomesUnresolvedReference(): void
    {
        $doc = new LsDoc();
        $item = new LsItem();
        $item->setLsDoc($doc);

        $assoc = new LsAssociation();
        $assoc->setLsDoc($doc);
        $assoc->setType(LsAssociation::RELATED_TO);
        $assoc->setOrigin($item);
        $assoc->setDestination('5de3f273-b43a-53c8-976c-4e8966b100a7');
        $item->addAssociation($assoc);

        $objectHelper = $this->createMock(\App\Service\IdentifiableObjectHelper::class);
        $objectHelper->method('findObjectByIdentifier')->willReturn(null);
        $collector = $this->makeCollector($objectHelper);

        $rows = $collector->collect($item);

        self::assertSame('unresolved', $rows['outgoing'][0]['kind']);
        self::assertSame('5de3f273-b43a-53c8-976c-4e8966b100a7', $rows['outgoing'][0]['unresolved']);
    }

    public function testBareIdentifierResolvedByLookup(): void
    {
        $doc = new LsDoc();
        $item = new LsItem();
        $item->setLsDoc($doc);

        $target = new LsItem();
        $target->setLsDoc($doc);
        $target->setAbbreviatedStatement('Recovered target');

        $assoc = new LsAssociation();
        $assoc->setLsDoc($doc);
        $assoc->setType(LsAssociation::RELATED_TO);
        $assoc->setOrigin($item);
        $assoc->setDestination('5de3f273-b43a-53c8-976c-4e8966b100a7');
        $item->addAssociation($assoc);

        $objectHelper = $this->createMock(\App\Service\IdentifiableObjectHelper::class);
        $objectHelper->method('findObjectByIdentifier')->willReturn($target);
        $collector = $this->makeCollector($objectHelper);

        $rows = $collector->collect($item);

        self::assertSame('1', $rows['outgoing'][0]['local']);
        self::assertSame('item', $rows['outgoing'][0]['kind']);
        self::assertStringContainsString('Recovered target', (string) $rows['outgoing'][0]['title']);
    }

    private function makeCollector(?\App\Service\IdentifiableObjectHelper $objectHelper = null): AssociationRowsCollector
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn (string $name, array $params = []) => 'https://localhost/uri/'.($params['uri'] ?? $params['id'] ?? '')
        );
        $uriGenerator = new UriGenerator($router);
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);
        $objectHelper ??= $this->createMock(\App\Service\IdentifiableObjectHelper::class);

        return new AssociationRowsCollector($authChecker, new Api1Uris($router, $uriGenerator), $uriGenerator, $objectHelper);
    }
}
