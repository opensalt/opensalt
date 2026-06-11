<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Repository\Framework;

use App\Entity\Framework\ObjectLock;
use App\Entity\LockableInterface;
use App\Entity\User\User;
use App\Exception\AlreadyLockedException;
use App\Repository\Framework\ObjectLockRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ObjectLockRepositoryTest extends TestCase
{
    private function createRepository(EntityManagerInterface $em, array $onlyMethods = []): ObjectLockRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->name = ObjectLock::class;
        $em->method('getClassMetadata')->willReturn($metadata);

        if ([] !== $onlyMethods) {
            return $this->getMockBuilder(ObjectLockRepository::class)
                ->setConstructorArgs([$registry])
                ->onlyMethods($onlyMethods)
                ->getMock();
        }

        return new ObjectLockRepository($registry);
    }

    public function testAcquireLockThrowsAlreadyLockedOnRaceCondition(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(
                $this->createMock(UniqueConstraintViolationException::class)
            );

        $otherUser = $this->createMock(User::class);
        $existingLock = $this->createMock(ObjectLock::class);
        $existingLock->method('getUser')->willReturn($otherUser);

        $repo = $this->createRepository($em, ['findLockFor']);
        $repo->expects($this->exactly(2))
            ->method('findLockFor')
            ->willReturnOnConsecutiveCalls(null, $existingLock);

        $obj = $this->createMock(LockableInterface::class);
        $obj->method('getId')->willReturn(42);
        $user = $this->createMock(User::class);

        $this->expectException(AlreadyLockedException::class);
        $this->expectExceptionMessage('Cannot acquire lock');

        $repo->acquireLock($obj, $user);
    }

    public function testAcquireLockReturnsExistingLockForSameUserOnRace(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(
                $this->createMock(UniqueConstraintViolationException::class)
            );

        $user = $this->createMock(User::class);
        $existingLock = $this->createMock(ObjectLock::class);
        $existingLock->method('getUser')->willReturn($user);

        $repo = $this->createRepository($em, ['findLockFor']);
        $repo->expects($this->exactly(2))
            ->method('findLockFor')
            ->willReturnOnConsecutiveCalls(null, $existingLock);

        $obj = $this->createMock(LockableInterface::class);
        $obj->method('getId')->willReturn(42);

        $result = $repo->acquireLock($obj, $user);
        $this->assertSame($existingLock, $result);
    }

    public function testAcquireLockSucceedsWhenNoContention(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $repo = $this->createRepository($em, ['findLockFor']);
        $repo->expects($this->once())
            ->method('findLockFor')
            ->willReturn(null);

        $obj = $this->createMock(LockableInterface::class);
        $obj->method('getId')->willReturn(42);
        $user = $this->createMock(User::class);

        $result = $repo->acquireLock($obj, $user);
        $this->assertInstanceOf(ObjectLock::class, $result);
    }

    public function testAcquireLockThrowsForDifferentUser(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $otherUser = $this->createMock(User::class);
        $user = $this->createMock(User::class);

        $existingLock = $this->createMock(ObjectLock::class);
        $existingLock->method('isExpired')->willReturn(false);
        $existingLock->method('getUser')->willReturn($otherUser);

        $repo = $this->createRepository($em, ['findLockFor']);
        $repo->expects($this->once())
            ->method('findLockFor')
            ->willReturn($existingLock);

        $obj = $this->createMock(LockableInterface::class);
        $obj->method('getId')->willReturn(42);

        $this->expectException(AlreadyLockedException::class);
        $repo->acquireLock($obj, $user);
    }
}
