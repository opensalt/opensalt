<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Repository\Framework;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsItemRepository;
use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TestLazyCollection extends AbstractLazyCollection implements Selectable
{
    public function __construct(ArrayCollection $collection)
    {
        $this->collection = $collection;
        $this->initialized = true;
    }

    protected function doInitialize(): void
    {
    }
}

class StubAssocRepository extends EntityRepository
{
    private int $callCount = 0;

    /**
     * @param TestLazyCollection[] $responses
     */
    public function __construct(
        EntityManagerInterface $em,
        ClassMetadata $classMetadata,
        private readonly array $responses = [],
    ) {
        parent::__construct($em, $classMetadata);
    }

    public function matching(Criteria $criteria): AbstractLazyCollection&Selectable
    {
        if ([] === $this->responses) {
            ++$this->callCount;
            return new TestLazyCollection(new ArrayCollection());
        }

        $idx = $this->callCount;
        ++$this->callCount;

        if (isset($this->responses[$idx])) {
            return $this->responses[$idx];
        }

        return new TestLazyCollection(new ArrayCollection());
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }
}

class LsItemRepositoryTest extends TestCase
{
    private ManagerRegistry|MockObject $managerRegistry;
    private EntityManagerInterface|MockObject $entityManager;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $classMetadata = new ClassMetadata(LsItem::class);

        $this->managerRegistry->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->method('getClassMetadata')
            ->willReturn($classMetadata);
    }

    private function createLazyCollection(array $elements): TestLazyCollection
    {
        return new TestLazyCollection(new ArrayCollection($elements));
    }

    private function createStubRepository(?LsItem $foundItem): LsItemRepository
    {
        return new class($this->managerRegistry, $foundItem) extends LsItemRepository {
            private ?LsItem $foundItem;

            public function __construct(ManagerRegistry $registry, ?LsItem $foundItem)
            {
                parent::__construct($registry);
                $this->foundItem = $foundItem;
            }

            public function findOneByIdentifier(string $identifier): ?LsItem
            {
                return $this->foundItem;
            }
        };
    }

    private function createAssocRepoWithResponses(array $responses): StubAssocRepository
    {
        $assocEm = $this->createMock(EntityManagerInterface::class);
        $assocMetadata = new ClassMetadata(LsAssociation::class);

        return new StubAssocRepository($assocEm, $assocMetadata, $responses);
    }

    public function testFindExactMatchesReturnsEmptyForUnknownIdentifier(): void
    {
        $repository = $this->createStubRepository(null);

        $result = $repository->findExactMatches('unknown-id');

        $this->assertSame([], $result);
    }

    public function testFindExactMatchesReturnsSingleItemWithNoAssociations(): void
    {
        $item = $this->createMock(LsItem::class);
        $item->method('getId')->willReturn(1);

        $assocRepo = $this->createAssocRepoWithResponses([
            $this->createLazyCollection([]),
        ]);

        $this->entityManager->method('getRepository')
            ->with(LsAssociation::class)
            ->willReturn($assocRepo);

        $repository = $this->createStubRepository($item);

        $result = $repository->findExactMatches('known-id');

        $this->assertCount(1, $result);
        $this->assertSame($item, $result[1]);
    }

    public function testFindExactMatchesRespectsMaxDepth(): void
    {
        $item1 = $this->createMock(LsItem::class);
        $item1->method('getId')->willReturn(1);

        $responses = [];
        for ($i = 0; $i < 12; ++$i) {
            $newItem = $this->createMock(LsItem::class);
            $newItem->method('getId')->willReturn(100 + $i);

            $assoc = $this->createMock(LsAssociation::class);
            $assoc->method('getDestinationLsItem')->willReturn($newItem);
            $assoc->method('getOriginLsItem')->willReturn($newItem);
            $responses[] = $this->createLazyCollection([$assoc]);
        }

        $assocRepo = $this->createAssocRepoWithResponses($responses);

        $this->entityManager->method('getRepository')
            ->with(LsAssociation::class)
            ->willReturn($assocRepo);

        $repository = $this->createStubRepository($item1);

        $result = $repository->findExactMatches('item-1', 2);

        $this->assertSame(4, $assocRepo->getCallCount());
        $this->assertCount(5, $result);
    }

    public function testFindExactMatchesDefaultDepthIsFive(): void
    {
        $item1 = $this->createMock(LsItem::class);
        $item1->method('getId')->willReturn(1);

        $responses = [];
        for ($i = 0; $i < 12; ++$i) {
            $newItem = $this->createMock(LsItem::class);
            $newItem->method('getId')->willReturn(100 + $i);

            $assoc = $this->createMock(LsAssociation::class);
            $assoc->method('getDestinationLsItem')->willReturn($newItem);
            $assoc->method('getOriginLsItem')->willReturn($newItem);
            $responses[] = $this->createLazyCollection([$assoc]);
        }

        $assocRepo = $this->createAssocRepoWithResponses($responses);

        $this->entityManager->method('getRepository')
            ->with(LsAssociation::class)
            ->willReturn($assocRepo);

        $repository = $this->createStubRepository($item1);

        $repository->findExactMatches('item-1');

        $this->assertSame(10, $assocRepo->getCallCount());
    }

    public function testFindExactMatchesExpandsTransitively(): void
    {
        $item1 = $this->createMock(LsItem::class);
        $item1->method('getId')->willReturn(1);

        $item2 = $this->createMock(LsItem::class);
        $item2->method('getId')->willReturn(2);

        $forwardAssoc = $this->createMock(LsAssociation::class);
        $forwardAssoc->method('getDestinationLsItem')->willReturn($item2);

        $emptyAssoc = $this->createMock(LsAssociation::class);
        $emptyAssoc->method('getOriginLsItem')->willReturn(null);

        $responses = [
            $this->createLazyCollection([$forwardAssoc]),
            $this->createLazyCollection([$emptyAssoc]),
            $this->createLazyCollection([$emptyAssoc]),
            $this->createLazyCollection([$emptyAssoc]),
        ];

        $assocRepo = $this->createAssocRepoWithResponses($responses);

        $this->entityManager->method('getRepository')
            ->with(LsAssociation::class)
            ->willReturn($assocRepo);

        $repository = $this->createStubRepository($item1);

        $result = $repository->findExactMatches('item-1', 10);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
    }
}
