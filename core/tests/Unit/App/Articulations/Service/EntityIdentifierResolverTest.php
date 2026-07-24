<?php

declare(strict_types=1);

namespace Tests\Unit\App\Articulations\Service;

use App\Articulations\Model\EntityIdentifiers;
use App\Articulations\Model\Identifier;
use App\Articulations\Model\InstitutionMatch;
use App\Articulations\Service\EntityIdentifierResolver;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsItemRepository;
use PHPUnit\Framework\TestCase;

final class EntityIdentifierResolverTest extends TestCase
{
    public function testFindSendingInstitutionMatchDelegatesToRepository(): void
    {
        $org = $this->createMock(LsItem::class);
        $request = EntityIdentifiers::fromArray([
            ['type' => 'coci:schoolId', 'value' => '46'],
        ]);
        $match = new InstitutionMatch($org, new Identifier('coci:schoolId', '46'));

        $itemRepository = $this->createMock(LsItemRepository::class);
        $itemRepository->expects($this->once())
            ->method('findInstitutionMatchByIdentifiers')
            ->with($request)
            ->willReturn($match);

        $resolver = new EntityIdentifierResolver($itemRepository);

        $this->assertSame($match, $resolver->findSendingInstitutionMatch($request));
    }

    public function testFindSendingInstitutionReturnsItemFromMatch(): void
    {
        $org = $this->createMock(LsItem::class);
        $request = EntityIdentifiers::fromArray([
            ['type' => 'coci:schoolId', 'value' => '46'],
        ]);
        $match = new InstitutionMatch($org, new Identifier('coci:schoolId', '46'));

        $itemRepository = $this->createMock(LsItemRepository::class);
        $itemRepository->method('findInstitutionMatchByIdentifiers')->willReturn($match);

        $resolver = new EntityIdentifierResolver($itemRepository);

        $this->assertSame($org, $resolver->findSendingInstitution($request));
    }

    public function testFindReceivingInstitutionMatchDelegatesToRepository(): void
    {
        $org = $this->createMock(LsItem::class);
        $request = EntityIdentifiers::fromArray([
            ['type' => 'codes:etsCode', 'value' => '4687'],
        ]);
        $match = new InstitutionMatch($org, new Identifier('codes:etsCode', '4687'));

        $itemRepository = $this->createMock(LsItemRepository::class);
        $itemRepository->expects($this->once())
            ->method('findInstitutionMatchByIdentifiers')
            ->with($request)
            ->willReturn($match);

        $resolver = new EntityIdentifierResolver($itemRepository);

        $this->assertSame($match, $resolver->findReceivingInstitutionMatch($request));
    }

    public function testFindReceivingInstitutionReturnsItemFromMatch(): void
    {
        $org = $this->createMock(LsItem::class);
        $request = EntityIdentifiers::fromArray([
            ['type' => 'codes:etsCode', 'value' => '4687'],
        ]);
        $match = new InstitutionMatch($org, new Identifier('codes:etsCode', '4687'));

        $itemRepository = $this->createMock(LsItemRepository::class);
        $itemRepository->method('findInstitutionMatchByIdentifiers')->willReturn($match);

        $resolver = new EntityIdentifierResolver($itemRepository);

        $this->assertSame($org, $resolver->findReceivingInstitution($request));
    }
}
