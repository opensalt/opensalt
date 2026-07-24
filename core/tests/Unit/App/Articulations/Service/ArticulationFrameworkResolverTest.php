<?php

declare(strict_types=1);

namespace Tests\Unit\App\Articulations\Service;

use App\Articulations\Model\ArticulationFrameworkResolution;
use App\Articulations\Model\EntityIdentifiers;
use App\Articulations\Model\Identifier;
use App\Articulations\Model\InstitutionMatch;
use App\Articulations\Service\ArticulationFrameworkResolver;
use App\Articulations\Service\EntityIdentifierResolver;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsDocRepository;
use App\Repository\Framework\LsItemRepository;
use PHPUnit\Framework\TestCase;

final class ArticulationFrameworkResolverTest extends TestCase
{
    public function testResolveReturnsHighestYearThenHighestId(): void
    {
        $docs = [
            $this->mockArticulationDoc(10, '2024-2025'),
            $this->mockArticulationDoc(20, '2025-2026'),
            $this->mockArticulationDoc(30, '2025-2026'),
        ];

        $resolver = $this->createResolver($docs, [10, 20, 30]);

        $sending = EntityIdentifiers::fromArray([
            ['type' => 'coci:schoolId', 'value' => '46'],
        ]);
        $receiving = EntityIdentifiers::fromArray([
            ['type' => 'codes:etsCode', 'value' => '4687'],
        ]);

        $resolved = $resolver->resolve($sending, $receiving);

        $this->assertInstanceOf(ArticulationFrameworkResolution::class, $resolved);
        $this->assertSame(30, $resolved->doc->getId());
        $this->assertSame('coci:schoolId', $resolved->sending->matchedIdentifier->type);
        $this->assertSame('codes:etsCode', $resolved->receiving->matchedIdentifier->type);
    }

    public function testResolveReturnsNullWhenInstitutionsDoNotMatch(): void
    {
        $itemRepository = $this->createMock(LsItemRepository::class);
        $itemRepository->method('findInstitutionMatchByIdentifiers')->willReturn(null);

        $resolver = new ArticulationFrameworkResolver(
            $this->createIdentifierResolver($itemRepository),
            $this->createMock(LsAssociationRepository::class),
            $this->createMock(LsDocRepository::class),
        );

        $sending = EntityIdentifiers::fromArray([
            ['type' => 'coci:schoolId', 'value' => '46'],
        ]);
        $receiving = EntityIdentifiers::fromArray([
            ['type' => 'codes:etsCode', 'value' => 'UNKNOWN'],
        ]);

        $this->assertNull($resolver->resolve($sending, $receiving));
    }

    public function testResolveReturnsNullWhenNoArticulationDocLinksInstitutions(): void
    {
        $sendingOrg = $this->mockOrganizationItem('send-10', ['coci:schoolId' => '46']);
        $receivingOrg = $this->mockOrganizationItem('recv-10', ['codes:etsCode' => '4687']);

        $resolver = $this->createResolver(
            [],
            [],
            $sendingOrg,
            $receivingOrg,
        );

        $sending = EntityIdentifiers::fromArray([
            ['type' => 'coci:schoolId', 'value' => '46'],
        ]);
        $receiving = EntityIdentifiers::fromArray([
            ['type' => 'codes:etsCode', 'value' => '4687'],
        ]);

        $this->assertNull($resolver->resolve($sending, $receiving));
    }

    /**
     * @param list<LsDoc> $docs
     * @param list<int> $docIds
     */
    private function createResolver(
        array $docs,
        array $docIds,
        ?LsItem $sendingOrg = null,
        ?LsItem $receivingOrg = null,
    ): ArticulationFrameworkResolver {
        $sendingOrg ??= $this->mockOrganizationItem('send-default', ['coci:schoolId' => '46']);
        $receivingOrg ??= $this->mockOrganizationItem('recv-default', ['codes:etsCode' => '4687']);

        $itemRepository = $this->createMock(LsItemRepository::class);
        $itemRepository->method('findInstitutionMatchByIdentifiers')->willReturnCallback(
            static function (EntityIdentifiers $request) use ($sendingOrg, $receivingOrg): ?InstitutionMatch {
                foreach ($request->identifiers as $identifier) {
                    if ('coci:schoolId' === $identifier->type) {
                        return new InstitutionMatch($sendingOrg, $identifier);
                    }
                    if ('codes:etsCode' === $identifier->type || 'identifier' === $identifier->type) {
                        return new InstitutionMatch($receivingOrg, $identifier);
                    }
                }

                return null;
            },
        );

        $associationRepository = $this->createMock(LsAssociationRepository::class);
        $associationRepository->method('findArticulationDocIdsForInstitutionItems')
            ->with($sendingOrg, $receivingOrg)
            ->willReturn($docIds);

        $docRepository = $this->createMock(LsDocRepository::class);
        $docRepository->method('findBy')->willReturnCallback(
            static function (array $criteria) use ($docs): array {
                $ids = $criteria['id'] ?? [];
                if (!is_array($ids)) {
                    return [];
                }

                return array_values(array_filter(
                    $docs,
                    static fn (LsDoc $doc): bool => in_array($doc->getId(), $ids, true),
                ));
            },
        );

        return new ArticulationFrameworkResolver(
            $this->createIdentifierResolver($itemRepository),
            $associationRepository,
            $docRepository,
        );
    }

    private function createIdentifierResolver(LsItemRepository $itemRepository): EntityIdentifierResolver
    {
        return new EntityIdentifierResolver($itemRepository);
    }

    private function mockArticulationDoc(int $id, string $yearCode): LsDoc
    {
        $doc = $this->createMock(LsDoc::class);
        $doc->method('getId')->willReturn($id);
        $doc->method('getIdentifier')->willReturn(sprintf('doc-%d', $id));
        $doc->method('getUri')->willReturn(sprintf('local:doc-%d', $id));
        $doc->method('getExtensions')->willReturn([
            'ais:academicYear' => ['code' => $yearCode],
        ]);

        return $doc;
    }

    /**
     * @param array<string, string> $fields
     */
    private function mockOrganizationItem(string $identifier, array $fields): LsItem
    {
        $item = $this->createMock(LsItem::class);
        $item->method('getId')->willReturn(crc32($identifier));
        $item->method('getIdentifier')->willReturn($identifier);
        $item->method('getUri')->willReturn('local:'.$identifier);
        $item->method('getHumanCodingScheme')->willReturn(null);
        $item->method('getExtensions')->willReturn($fields);
        $item->method('getDiscriminator')->willReturn(LsItemKind::Organization->value);

        return $item;
    }
}
