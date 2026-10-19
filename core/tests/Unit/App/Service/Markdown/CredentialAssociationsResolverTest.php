<?php

declare(strict_types=1);

namespace Tests\Unit\App\Service\Markdown;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsItemRepository;
use App\Service\Markdown\CredentialAssociationsResolver;

class CredentialAssociationsResolverTest extends \Codeception\Test\Unit
{
    public function testIncomingPrecedesBecomesCriteriaAndOthersAlignments(): void
    {
        $item = new LsItem();
        $criterion = new LsItem();
        $alignment = new LsItem();

        $inPrecedes = new LsAssociation();
        $inPrecedes->setType(LsAssociation::PRECEDES);
        $inPrecedes->setOrigin($criterion);
        $inPrecedes->setDestination($item);

        $inRelated = new LsAssociation();
        $inRelated->setType(LsAssociation::RELATED_TO);
        $inRelated->setOrigin($alignment);
        $inRelated->setDestination($item);

        $assocRepo = $this->createMock(LsAssociationRepository::class);
        $assocRepo->method('findAllAssociationsForAsSplitArray')->willReturn([
            'associations' => [],
            'inverseAssociations' => [$inPrecedes, $inRelated],
        ]);
        $itemRepo = $this->createMock(LsItemRepository::class);

        $resolver = new CredentialAssociationsResolver($assocRepo, $itemRepo);
        $result = $resolver->resolve($item);

        self::assertSame([$criterion], array_values($result['criteria']));
        self::assertSame([$alignment], array_values($result['alignments']));
    }

    public function testOutgoingNonDocDestinationIsAlignment(): void
    {
        $item = new LsItem();
        $target = new LsItem();

        $out = new LsAssociation();
        $out->setType(LsAssociation::RELATED_TO);
        $out->setOrigin($item);
        $out->setDestination($target);

        $assocRepo = $this->createMock(LsAssociationRepository::class);
        $assocRepo->method('findAllAssociationsForAsSplitArray')->willReturn([
            'associations' => [$out],
            'inverseAssociations' => [],
        ]);

        $resolver = new CredentialAssociationsResolver($assocRepo, $this->createMock(LsItemRepository::class));
        $result = $resolver->resolve($item);

        self::assertSame([], $result['criteria']);
        self::assertSame([$target], array_values($result['alignments']));
    }

    public function testCriteriaAreRemovedFromAlignments(): void
    {
        $item = new LsItem();
        $both = new LsItem();

        $out = new LsAssociation();
        $out->setType(LsAssociation::RELATED_TO);
        $out->setOrigin($item);
        $out->setDestination($both);

        $inPrecedes = new LsAssociation();
        $inPrecedes->setType(LsAssociation::PRECEDES);
        $inPrecedes->setOrigin($both);
        $inPrecedes->setDestination($item);

        $assocRepo = $this->createMock(LsAssociationRepository::class);
        $assocRepo->method('findAllAssociationsForAsSplitArray')->willReturn([
            'associations' => [$out],
            'inverseAssociations' => [$inPrecedes],
        ]);

        $resolver = new CredentialAssociationsResolver($assocRepo, $this->createMock(LsItemRepository::class));
        $result = $resolver->resolve($item);

        self::assertSame([$both], array_values($result['criteria']));
        self::assertSame([], $result['alignments']);
    }

    public function testExemplarImagesCollectedFromOutgoing(): void
    {
        $item = new LsItem();

        $exemplar = new LsAssociation();
        $exemplar->setType(LsAssociation::EXEMPLAR);
        $exemplar->setOrigin($item);
        $exemplar->setDestination('https://example.com/badge.svg');

        $other = new LsAssociation();
        $other->setType(LsAssociation::RELATED_TO);
        $other->setOrigin($item);
        $other->setDestination('https://example.com/page');

        $assocRepo = $this->createMock(LsAssociationRepository::class);
        $assocRepo->method('findAllAssociationsForAsSplitArray')->willReturn([
            'associations' => [$exemplar, $other],
            'inverseAssociations' => [],
        ]);

        $resolver = new CredentialAssociationsResolver($assocRepo, $this->createMock(LsItemRepository::class));

        self::assertSame(['https://example.com/badge.svg'], $resolver->getExemplarImages($item));
    }
}
