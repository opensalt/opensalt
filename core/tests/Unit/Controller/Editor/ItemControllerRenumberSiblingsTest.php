<?php

declare(strict_types=1);

namespace Tests\Unit\Controller\Editor;

use App\Controller\Editor\ItemController;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsDocRepository;
use App\Repository\Framework\LsItemRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

#[AllowMockObjectsWithoutExpectations]
final class ItemControllerRenumberSiblingsTest extends TestCase
{
    private function invokeRenumberSiblings(
        ItemController $controller,
        LsItem $parent,
        ?string $movedItemIdentifier = null,
        ?string $targetItemIdentifier = null,
        ?string $position = null,
    ): void {
        $method = new \ReflectionMethod($controller, 'renumberSiblings');
        $method->invoke($controller, $parent, $movedItemIdentifier, $targetItemIdentifier, $position);
    }

    private function buildController(array $childAssocs): ItemController
    {
        $repo = $this->createMock(LsAssociationRepository::class);
        $repo->method('findAllChildAssociationsFor')->willReturn($childAssocs);

        return new ItemController(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(HtmlSanitizerInterface::class),
            $this->createMock(LsDocRepository::class),
            $this->createMock(LsItemRepository::class),
            $repo,
            $this->createMock(Security::class),
        );
    }

    private function createAssoc(?int $seqNumber, string $originItemIdentifier, array &$assignedSeqs): LsAssociation
    {
        $lsItem = $this->createMock(LsItem::class);
        $lsItem->method('getIdentifier')->willReturn($originItemIdentifier);

        $assoc = $this->createMock(LsAssociation::class);
        $assoc->method('getSequenceNumber')->willReturn($seqNumber);
        $assoc->method('getOriginLsItem')->willReturn($lsItem);
        $assoc->method('setSequenceNumber')->willReturnCallback(
            static function (int|string|null $s) use ($originItemIdentifier, $assoc, &$assignedSeqs): LsAssociation {
                $assignedSeqs[$originItemIdentifier] = (int) $s;

                return $assoc;
            },
        );

        return $assoc;
    }

    private function createParent(): LsItem
    {
        $parent = $this->createMock(LsItem::class);
        $parent->method('getIdentifier')->willReturn('parent-1');

        return $parent;
    }

    public function testMoveAfterWithSameSequenceNumber(): void
    {
        $assignedSeqs = [];
        $assocA = $this->createAssoc(1, 'item-A', $assignedSeqs);
        $assocB = $this->createAssoc(1, 'item-B', $assignedSeqs);

        $controller = $this->buildController([$assocA, $assocB]);
        $this->invokeRenumberSiblings($controller, $this->createParent(), 'item-A', 'item-B', 'after');

        self::assertGreaterThan($assignedSeqs['item-B'], $assignedSeqs['item-A'],
            'Item A (moved after B) should receive a higher sequence number than B');
    }

    public function testMoveBeforeWithSameSequenceNumber(): void
    {
        $assignedSeqs = [];
        $assocA = $this->createAssoc(1, 'item-A', $assignedSeqs);
        $assocB = $this->createAssoc(1, 'item-B', $assignedSeqs);

        $controller = $this->buildController([$assocA, $assocB]);
        $this->invokeRenumberSiblings($controller, $this->createParent(), 'item-A', 'item-B', 'before');

        self::assertLessThan($assignedSeqs['item-B'], $assignedSeqs['item-A'],
            'Item A (moved before B) should receive a lower sequence number than B');
    }

    public function testDifferentSequenceNumbersRespectsPosition(): void
    {
        $assignedSeqs = [];
        $assocA = $this->createAssoc(1, 'item-A', $assignedSeqs);
        $assocB = $this->createAssoc(2, 'item-B', $assignedSeqs);

        $controller = $this->buildController([$assocB, $assocA]);
        $this->invokeRenumberSiblings($controller, $this->createParent(), 'item-A', 'item-B', 'after');

        self::assertGreaterThan($assignedSeqs['item-B'], $assignedSeqs['item-A'],
            'A explicitly moved after B should receive a higher sequence number');
    }

    public function testReverseDifferentSequenceNumbers(): void
    {
        $assignedSeqs = [];
        $assocA = $this->createAssoc(2, 'item-A', $assignedSeqs);
        $assocB = $this->createAssoc(1, 'item-B', $assignedSeqs);

        $controller = $this->buildController([$assocA, $assocB]);
        $this->invokeRenumberSiblings($controller, $this->createParent(), 'item-A', 'item-B', 'before');

        self::assertLessThan($assignedSeqs['item-B'], $assignedSeqs['item-A'],
            'A explicitly moved before B should receive a lower sequence number');
    }

    public function testThreeItemsCollisionMoveAfter(): void
    {
        $assignedSeqs = [];
        $assocY = $this->createAssoc(1, 'item-Y', $assignedSeqs);
        $assocX = $this->createAssoc(1, 'item-X', $assignedSeqs);
        $assocZ = $this->createAssoc(2, 'item-Z', $assignedSeqs);

        $controller = $this->buildController([$assocY, $assocX, $assocZ]);
        $this->invokeRenumberSiblings($controller, $this->createParent(), 'item-X', 'item-Y', 'after');

        self::assertLessThan($assignedSeqs['item-X'], $assignedSeqs['item-Y'],
            'Y should come before X when X is moved after Y');
        self::assertLessThan($assignedSeqs['item-Z'], $assignedSeqs['item-X'],
            'X should come before Z (X gets seq 2, Z gets seq 3)');
    }

    public function testNoPositionInfoFallsBackToSequenceOnly(): void
    {
        $assignedSeqs = [];
        $assocA = $this->createAssoc(1, 'item-A', $assignedSeqs);
        $assocB = $this->createAssoc(1, 'item-B', $assignedSeqs);

        $controller = $this->buildController([$assocA, $assocB]);
        $this->invokeRenumberSiblings($controller, $this->createParent());

        self::assertCount(2, $assignedSeqs);
        self::assertSame(1, $assignedSeqs['item-A']);
        self::assertSame(2, $assignedSeqs['item-B']);
    }

    public function testNullSequenceNumbersTreatedAsZero(): void
    {
        $assignedSeqs = [];
        $assocA = $this->createAssoc(null, 'item-A', $assignedSeqs);
        $assocB = $this->createAssoc(null, 'item-B', $assignedSeqs);

        $controller = $this->buildController([$assocA, $assocB]);
        $this->invokeRenumberSiblings($controller, $this->createParent(), 'item-A', 'item-B', 'after');

        self::assertGreaterThan($assignedSeqs['item-B'], $assignedSeqs['item-A'],
            'A (moved after B) should sort after B when both seq are null');
    }

    public function testMoveBeforeFirstItem(): void
    {
        $assignedSeqs = [];
        $assocA = $this->createAssoc(1, 'item-A', $assignedSeqs);
        $assocB = $this->createAssoc(2, 'item-B', $assignedSeqs);
        $assocC = $this->createAssoc(3, 'item-C', $assignedSeqs);

        $controller = $this->buildController([$assocA, $assocB, $assocC]);
        $this->invokeRenumberSiblings($controller, $this->createParent(), 'item-C', 'item-A', 'before');

        self::assertSame(1, $assignedSeqs['item-C'], 'C moved before A should get seq 1');
        self::assertSame(2, $assignedSeqs['item-A']);
        self::assertSame(3, $assignedSeqs['item-B']);
    }

    public function testMoveAfterLastItem(): void
    {
        $assignedSeqs = [];
        $assocA = $this->createAssoc(1, 'item-A', $assignedSeqs);
        $assocB = $this->createAssoc(2, 'item-B', $assignedSeqs);
        $assocC = $this->createAssoc(3, 'item-C', $assignedSeqs);

        $controller = $this->buildController([$assocA, $assocB, $assocC]);
        $this->invokeRenumberSiblings($controller, $this->createParent(), 'item-A', 'item-C', 'after');

        self::assertSame(3, $assignedSeqs['item-A'], 'A moved after C should get seq 3');
        self::assertSame(1, $assignedSeqs['item-B']);
        self::assertSame(2, $assignedSeqs['item-C']);
    }

    public function testMoveBeforeMiddleItem(): void
    {
        $assignedSeqs = [];
        $assocA = $this->createAssoc(1, 'item-A', $assignedSeqs);
        $assocB = $this->createAssoc(2, 'item-B', $assignedSeqs);
        $assocC = $this->createAssoc(3, 'item-C', $assignedSeqs);

        $controller = $this->buildController([$assocA, $assocB, $assocC]);
        $this->invokeRenumberSiblings($controller, $this->createParent(), 'item-C', 'item-B', 'before');

        self::assertSame(1, $assignedSeqs['item-A']);
        self::assertSame(2, $assignedSeqs['item-C'], 'C moved before B should get seq 2');
        self::assertSame(3, $assignedSeqs['item-B']);
    }

    public function testMoveAfterMiddleItem(): void
    {
        $assignedSeqs = [];
        $assocA = $this->createAssoc(1, 'item-A', $assignedSeqs);
        $assocB = $this->createAssoc(2, 'item-B', $assignedSeqs);
        $assocC = $this->createAssoc(3, 'item-C', $assignedSeqs);

        $controller = $this->buildController([$assocA, $assocB, $assocC]);
        $this->invokeRenumberSiblings($controller, $this->createParent(), 'item-A', 'item-B', 'after');

        self::assertSame(2, $assignedSeqs['item-A'], 'A moved after B should get seq 2');
        self::assertSame(1, $assignedSeqs['item-B']);
        self::assertSame(3, $assignedSeqs['item-C']);
    }

    public function testInsidePositionDoesNotReorder(): void
    {
        $assignedSeqs = [];
        $assocA = $this->createAssoc(5, 'item-A', $assignedSeqs);
        $assocB = $this->createAssoc(2, 'item-B', $assignedSeqs);

        $controller = $this->buildController([$assocA, $assocB]);
        $this->invokeRenumberSiblings($controller, $this->createParent(), 'item-A', 'item-B', 'inside');

        self::assertSame(1, $assignedSeqs['item-B'], 'B (seq=2) sorts first after renumber');
        self::assertSame(2, $assignedSeqs['item-A'], 'A (seq=5) sorts second after renumber');
    }
}
