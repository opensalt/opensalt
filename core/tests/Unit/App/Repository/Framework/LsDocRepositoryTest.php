<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Repository\Framework;

use App\Entity\Framework\LsDoc;
use App\Repository\Framework\LsDocRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class LsDocRepositoryTest extends TestCase
{
    private function createRepository(EntityManagerInterface $em): LsDocRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $metadata = $this->createMock(\Doctrine\ORM\Mapping\ClassMetadata::class);
        $metadata->name = LsDoc::class;
        $em->method('getClassMetadata')->willReturn($metadata);

        $security = $this->createMock(Security::class);

        return new LsDocRepository($registry, $security);
    }

    public function testDeleteDocumentWrapsInTransaction(): void
    {
        $connection = $this->createMock(Connection::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        $result = $this->createMock(\Doctrine\DBAL\Result::class);
        $result->method('rowCount')->willReturn(0);

        $transactionStarted = false;
        $committed = false;

        $connection->expects($this->once())
            ->method('beginTransaction')
            ->willReturnCallback(function () use (&$transactionStarted) {
                $transactionStarted = true;
            });

        $connection->expects($this->once())
            ->method('commit')
            ->willReturnCallback(function () use (&$committed) {
                $committed = true;
            });

        $connection->method('prepare')
            ->willReturnCallback(function () use ($result) {
                $stmt = $this->createMock(Statement::class);
                $stmt->method('bindValue');
                $stmt->method('executeStatement')->willReturn(0);

                return $stmt;
            });

        $repo = $this->createRepository($em);

        $doc = $this->createMock(LsDoc::class);
        $doc->method('getId')->willReturn(42);

        $messages = [];
        $repo->deleteDocument($doc, function (string $msg) use (&$messages) {
            $messages[] = $msg;
        });

        $this->assertTrue($transactionStarted, 'beginTransaction should have been called');
        $this->assertTrue($committed, 'commit should have been called');
        $this->assertContains('Done', $messages);
    }

    public function testDeleteDocumentRollsBackOnFailure(): void
    {
        $connection = $this->createMock(Connection::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        $rolledBack = false;

        $connection->expects($this->once())
            ->method('beginTransaction');

        $connection->expects($this->never())
            ->method('commit');

        $connection->expects($this->once())
            ->method('rollBack')
            ->willReturnCallback(function () use (&$rolledBack) {
                $rolledBack = true;
            });

        $callCount = 0;
        $connection->method('prepare')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if ($callCount > 3) {
                    throw new \RuntimeException('Simulated DB failure');
                }
                $stmt = $this->createMock(Statement::class);
                $stmt->method('bindValue');
                $stmt->method('executeStatement')->willReturn(0);

                return $stmt;
            });

        $repo = $this->createRepository($em);

        $doc = $this->createMock(LsDoc::class);
        $doc->method('getId')->willReturn(42);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Simulated DB failure');

        try {
            $repo->deleteDocument($doc);
        } finally {
            $this->assertTrue($rolledBack, 'rollBack should have been called');
        }
    }

    public function testBuildFullNodeIncludesNotes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createRepository($em);

        $itemTypeRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $itemTypeCache = [];

        $method = new \ReflectionMethod(LsDocRepository::class, 'buildFullNode');

        $args = [
            'item-1',
            [
                'identifier' => 'item-1',
                'uri' => 'https://example.org/uri/item-1',
                'fullStatement' => 'An item with notes',
                'notes' => 'Denominators are limited to 2, 3, 4, 6, and 8 in third grade',
                'changedAt' => new \DateTimeImmutable('2024-01-01T00:00:00Z'),
            ],
            'doc-1',
            null,
            false,
            [],
            $itemTypeRepo,
        ];
        $args[] = &$itemTypeCache;
        $args[] = [];
        $args[] = [];

        $node = $method->invokeArgs($repo, $args);

        $this->assertArrayHasKey('notes', $node);
        $this->assertSame('Denominators are limited to 2, 3, 4, 6, and 8 in third grade', $node['notes']);
    }
}
