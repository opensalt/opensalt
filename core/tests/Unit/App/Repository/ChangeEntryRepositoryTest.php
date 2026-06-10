<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Repository;

use App\Entity\ChangeEntry;
use App\Event\NotificationEvent;
use App\Repository\ChangeEntryRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ChangeEntryRepositoryTest extends TestCase
{
    public function testUpdateChangedWithNullIdThrowsLogicException(): void
    {
        $repo = $this->createRepository();

        $change = new ChangeEntry(null, null, 'Test description', []);

        $notification = new NotificationEvent('T01', 'Test', null, ['key' => 'value'], false);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ChangeEntry ID is null in updateChanged()');

        $repo->updateChanged($change, $notification);
    }

    public function testUpdateChangedWithIdDoesNotThrow(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('executeStatement')
            ->willReturn(1);

        $repo = $this->createRepository($connection);

        $change = new ChangeEntry(null, null, 'Test description', []);
        $ref = new \ReflectionClass($change);
        $prop = $ref->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($change, 42);

        $notification = new NotificationEvent('T01', 'Test', null, ['key' => 'value'], false);

        $repo->updateChanged($change, $notification);

        $this->assertSame('42', $change->getId());
    }

    private function createRepository(?Connection $connection = null): ChangeEntryRepository
    {
        $connection ??= $this->createMock(Connection::class);

        $metadata = new ClassMetadata(ChangeEntry::class);
        $metadata->setTableName('salt_change');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->willReturn($metadata);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return new ChangeEntryRepository($registry);
    }
}
