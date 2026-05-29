<?php

declare(strict_types=1);

namespace Tests\Unit\App\DataTransformer\CaseJson;

use App\DataTransformer\CaseJson\UriCollisionResolver;
use App\Entity\Framework\LsDefItemType;
use App\Repository\Framework\LsDefItemTypeRepository;

class UriCollisionResolverTest extends \Codeception\Test\Unit
{
    private function createEntity(string $identifier, string $uri): LsDefItemType
    {
        $entity = new LsDefItemType($identifier);
        $entity->setUri($uri);

        return $entity;
    }

    private function createRepositoryNoCollision(): LsDefItemTypeRepository
    {
        $repository = $this->createMock(LsDefItemTypeRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        return $repository;
    }

    public function testNoCollisions(): void
    {
        $entity1 = $this->createEntity(
            '11111111-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/uri-1'
        );
        $entity2 = $this->createEntity(
            '22222222-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/uri-2'
        );
        $entity3 = $this->createEntity(
            '33333333-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/uri-3'
        );

        $originalUris = [
            $entity1->getUri(),
            $entity2->getUri(),
            $entity3->getUri(),
        ];

        UriCollisionResolver::resolve([$entity1, $entity2, $entity3], $this->createRepositoryNoCollision());

        $this->assertSame($originalUris[0], $entity1->getUri());
        $this->assertSame($originalUris[1], $entity2->getUri());
        $this->assertSame($originalUris[2], $entity3->getUri());
    }

    public function testCollisionWithinBatch(): void
    {
        $entity1 = $this->createEntity(
            '11111111-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/same-uri'
        );
        $entity2 = $this->createEntity(
            '22222222-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/same-uri'
        );

        UriCollisionResolver::resolve([$entity1, $entity2], $this->createRepositoryNoCollision());

        $this->assertSame('https://example.com/same-uri', $entity1->getUri());
        $this->assertSame(
            'https://example.com/same-uri#22222222-aaaa-bbbb-cccc-dddddddddddd',
            $entity2->getUri()
        );
    }

    public function testCollisionWithDatabase(): void
    {
        $batchEntity = $this->createEntity(
            '11111111-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/colliding-uri'
        );

        $dbEntity = $this->createEntity(
            '99999999-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/colliding-uri'
        );

        $repository = $this->createMock(LsDefItemTypeRepository::class);
        $repository->method('findOneBy')->willReturnCallback(
            function (array $criteria) use ($dbEntity) {
                if (($criteria['uri'] ?? null) === 'https://example.com/colliding-uri') {
                    return $dbEntity;
                }

                return null;
            }
        );

        UriCollisionResolver::resolve([$batchEntity], $repository);

        $this->assertSame(
            'https://example.com/colliding-uri#11111111-aaaa-bbbb-cccc-dddddddddddd',
            $batchEntity->getUri()
        );
    }

    public function testCollisionDatabaseExcludedWhenSameEntity(): void
    {
        $identifier = '11111111-aaaa-bbbb-cccc-dddddddddddd';

        $entity = $this->createEntity(
            $identifier,
            'https://example.com/same-entity-uri'
        );

        $dbEntity = $this->createEntity(
            $identifier,
            'https://example.com/same-entity-uri'
        );

        $repository = $this->createMock(LsDefItemTypeRepository::class);
        $repository->method('findOneBy')->willReturnCallback(
            function (array $criteria) use ($dbEntity) {
                if (($criteria['uri'] ?? null) === 'https://example.com/same-entity-uri') {
                    return $dbEntity;
                }

                return null;
            }
        );

        UriCollisionResolver::resolve([$entity], $repository);

        $this->assertSame('https://example.com/same-entity-uri', $entity->getUri());
    }

    public function testMultipleCollisionsWithinBatch(): void
    {
        $entity1 = $this->createEntity(
            '11111111-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/shared-uri'
        );
        $entity2 = $this->createEntity(
            '22222222-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/shared-uri'
        );
        $entity3 = $this->createEntity(
            '33333333-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/shared-uri'
        );

        UriCollisionResolver::resolve(
            [$entity1, $entity2, $entity3],
            $this->createRepositoryNoCollision()
        );

        $this->assertSame('https://example.com/shared-uri', $entity1->getUri());
        $this->assertSame(
            'https://example.com/shared-uri#22222222-aaaa-bbbb-cccc-dddddddddddd',
            $entity2->getUri()
        );
        $this->assertSame(
            'https://example.com/shared-uri#33333333-aaaa-bbbb-cccc-dddddddddddd',
            $entity3->getUri()
        );
    }

    public function testEmptyArray(): void
    {
        $repository = $this->createMock(LsDefItemTypeRepository::class);
        $repository->expects($this->never())->method('findOneBy');

        UriCollisionResolver::resolve([], $repository);

        $this->assertTrue(true);
    }

    public function testMixedCollisions(): void
    {
        $unique1 = $this->createEntity(
            '11111111-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/unique-a'
        );
        $colliding1 = $this->createEntity(
            '22222222-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/duplicate'
        );
        $unique2 = $this->createEntity(
            '33333333-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/unique-b'
        );
        $colliding2 = $this->createEntity(
            '44444444-aaaa-bbbb-cccc-dddddddddddd',
            'https://example.com/duplicate'
        );

        UriCollisionResolver::resolve(
            [$unique1, $colliding1, $unique2, $colliding2],
            $this->createRepositoryNoCollision()
        );

        $this->assertSame('https://example.com/unique-a', $unique1->getUri());
        $this->assertSame('https://example.com/duplicate', $colliding1->getUri());
        $this->assertSame('https://example.com/unique-b', $unique2->getUri());
        $this->assertSame(
            'https://example.com/duplicate#44444444-aaaa-bbbb-cccc-dddddddddddd',
            $colliding2->getUri()
        );
    }
}
