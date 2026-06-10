<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Repository\Framework\Mirror;

use App\Entity\Framework\Mirror\Server;
use App\Repository\Framework\Mirror\ServerRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ServerRepositoryTest extends TestCase
{
    public function testFindNextUsesPessimisticWriteLock(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('setLockMode')
            ->with(LockMode::PESSIMISTIC_WRITE)
            ->willReturnSelf();
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->name = Server::class;
        $em->method('getClassMetadata')->willReturn($metadata);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $repo = new ServerRepository($registry);
        $result = $repo->findNext();

        $this->assertNull($result);
    }
}
