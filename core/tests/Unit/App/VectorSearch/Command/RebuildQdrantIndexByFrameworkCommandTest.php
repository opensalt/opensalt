<?php

declare(strict_types=1);

namespace Tests\Unit\App\VectorSearch\Command;

use App\VectorSearch\Command\RebuildQdrantIndexByFrameworkCommand;
use App\VectorSearch\Service\VectorSearchService;
use App\VectorSearch\Store\HybridQdrantStore;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\Result\DeferredResult;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\Component\Console\Tester\CommandTester;

final class RebuildQdrantIndexByFrameworkCommandTest extends TestCase
{
    public function testExecuteResumesSpecifiedExistingCollection(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createMock(PlatformInterface::class);
        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $qdrantStore = $this->createMock(HybridQdrantStore::class);

        $this->mockFrameworkQuery($connection, []);

        $qdrantStore->expects(self::once())
            ->method('getCollectionAlias')
            ->willReturn('framework_alias');
        $qdrantStore->expects(self::once())
            ->method('getResolvedActiveCollectionName')
            ->willReturn('framework_alias__active');
        $qdrantStore->expects(self::once())
            ->method('prepareCollection')
            ->with('resume_collection', false, true)
            ->willReturn(false);
        $qdrantStore->expects(self::exactly(2))
            ->method('countCollection')
            ->with('resume_collection')
            ->willReturn(5);

        $command = new RebuildQdrantIndexByFrameworkCommand(
            $connection,
            $platform,
            $vectorSearchService,
            $qdrantStore,
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            '--collection' => 'resume_collection',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Resuming existing target collection: resume_collection (5 points already present)', $tester->getDisplay());
        self::assertStringContainsString('Finished rebuilding shadow collection resume_collection.', $tester->getDisplay());
    }

    public function testExecuteSkipsEmptyRowsBeforeEmbeddingGeneration(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createMock(PlatformInterface::class);
        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $qdrantStore = $this->createMock(HybridQdrantStore::class);

        $frameworks = [[
            'id' => '10',
            'identifier' => 'fw-10',
            'title' => 'Framework 10',
        ]];
        $this->mockFrameworkQuery($connection, $frameworks);
        $connection->expects(self::once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(*) FROM ls_item WHERE ls_doc_id = :frameworkId',
                ['frameworkId' => 10],
                ['frameworkId' => \Doctrine\DBAL\ParameterType::INTEGER]
            )
            ->willReturn('2');

        $timestamp = new \DateTimeImmutable('2026-03-31T10:00:00+00:00');
        $vectorRows = [
            [
                'lsItemId' => 100,
                'frameworkId' => 10,
                'kind' => 1,
                'text' => '',
                'isLeafNode' => true,
                'sourceHierarchyUpdatedAt' => $timestamp,
            ],
            [
                'lsItemId' => 101,
                'frameworkId' => 10,
                'kind' => 1,
                'text' => 'Valid statement',
                'isLeafNode' => true,
                'sourceHierarchyUpdatedAt' => $timestamp,
            ],
        ];

        $qdrantStore->expects(self::once())
            ->method('getCollectionAlias')
            ->willReturn('framework_alias');
        $qdrantStore->expects(self::once())
            ->method('getResolvedActiveCollectionName')
            ->willReturn('framework_alias__active');
        $qdrantStore->expects(self::once())
            ->method('prepareCollection')
            ->with('resume_collection', false, true)
            ->willReturn(true);
        $qdrantStore->expects(self::once())
            ->method('countCollection')
            ->with('resume_collection')
            ->willReturn(1);
        $qdrantStore->expects(self::once())
            ->method('importEmbeddings')
            ->with(
                self::callback(static function (array $rows): bool {
                    return 1 === count($rows)
                        && 101 === $rows[0]['lsItemId']
                        && 'Valid statement' === $rows[0]['text']
                        && [0.1, 0.2, 0.3] === $rows[0]['vector'];
                }),
                'resume_collection'
            )
            ->willReturn(1);

        $vectorSearchService->expects(self::once())
            ->method('buildAllEmbeddingRowsForFramework')
            ->with(10)
            ->willReturn($vectorRows);

        $rawResult = $this->createMock(\Symfony\AI\Platform\Result\RawResultInterface::class);
        $vectorResult = new \Symfony\AI\Platform\Result\VectorResult([new Vector([0.1, 0.2, 0.3])]);
        $resultConverter = $this->createMock(\Symfony\AI\Platform\ResultConverterInterface::class);
        $resultConverter->expects(self::once())
            ->method('convert')
            ->willReturn($vectorResult);
        $deferredResult = new \Symfony\AI\Platform\Result\DeferredResult($resultConverter, $rawResult);

        $platform->expects(self::once())
            ->method('invoke')
            ->with('Xenova/all-MiniLM-L6-v2', ['Valid statement'])
            ->willReturn($deferredResult);

        $command = new RebuildQdrantIndexByFrameworkCommand(
            $connection,
            $platform,
            $vectorSearchService,
            $qdrantStore,
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            '--collection' => 'resume_collection',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Skipping 1 items with empty embedding text.', $tester->getDisplay());
        self::assertStringContainsString('Batch 1/1: 1/1 items imported', $tester->getDisplay());
    }

    /**
     * @param list<array{id: string, identifier: string, title: string}> $frameworks
     */
    private function mockFrameworkQuery(Connection $connection, array $frameworks): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $result = $this->createMock(Result::class);

        $connection->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->expects(self::never())->method('where');
        $queryBuilder->expects(self::never())->method('setParameter');
        $queryBuilder->expects(self::once())
            ->method('executeQuery')
            ->willReturn($result);

        $result->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn($frameworks);
    }
}
