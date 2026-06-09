<?php

declare(strict_types=1);

namespace Tests\Unit\App\Service;

use App\Entity\Framework\AdditionalField;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsDocRepository;
use App\Service\ExcelExport;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExcelExportTest extends TestCase
{
    public function testMaxExportSizeDefaultMatchesRepositoryConstant(): void
    {
        $export = new ExcelExport($this->createStub(EntityManagerInterface::class));
        $reflection = new \ReflectionClass($export);
        $prop = $reflection->getProperty('maxExportSize');
        $this->assertSame(LsDocRepository::MAX_RESULTS, $prop->getValue($export));
    }

    public function testMaxExportSizeCustomValueIsUsed(): void
    {
        $export = new ExcelExport($this->createStub(EntityManagerInterface::class), 1000);
        $reflection = new \ReflectionClass($export);
        $prop = $reflection->getProperty('maxExportSize');
        $this->assertSame(1000, $prop->getValue($export));
    }

    public function testExportThrowsHttpExceptionWhenCountExceedsMax(): void
    {
        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn('60000');

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $lsItemRepo = $this->createMock(EntityRepository::class);
        $lsItemRepo->method('createQueryBuilder')->willReturn($qb);

        $lsDocRepo = $this->createMock(LsDocRepository::class);
        $additionalFieldRepo = $this->createMock(ObjectRepository::class);
        $additionalFieldRepo->method('findBy')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->willReturnCallback(function (string $class) use ($additionalFieldRepo, $lsItemRepo, $lsDocRepo) {
                return match ($class) {
                    AdditionalField::class => $additionalFieldRepo,
                    LsItem::class => $lsItemRepo,
                    LsDoc::class => $lsDocRepo,
                    default => $this->createMock(EntityRepository::class),
                };
            });

        $export = new ExcelExport($em, 50000);

        $doc = $this->createMock(LsDoc::class);
        $doc->method('getId')->willReturn(1);

        $this->expectException(HttpException::class);
        $export->exportExcelFile($doc);
    }

    public function testExportHttpExceptionHas413StatusCode(): void
    {
        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn('100000');

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $lsItemRepo = $this->createMock(EntityRepository::class);
        $lsItemRepo->method('createQueryBuilder')->willReturn($qb);

        $lsDocRepo = $this->createMock(LsDocRepository::class);
        $additionalFieldRepo = $this->createMock(ObjectRepository::class);
        $additionalFieldRepo->method('findBy')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->willReturnCallback(function (string $class) use ($additionalFieldRepo, $lsItemRepo, $lsDocRepo) {
                return match ($class) {
                    AdditionalField::class => $additionalFieldRepo,
                    LsItem::class => $lsItemRepo,
                    LsDoc::class => $lsDocRepo,
                    default => $this->createMock(EntityRepository::class),
                };
            });

        $export = new ExcelExport($em, 50000);

        $doc = $this->createMock(LsDoc::class);
        $doc->method('getId')->willReturn(1);

        try {
            $export->exportExcelFile($doc);
            $this->fail('Expected HttpException was not thrown');
        } catch (HttpException $e) {
            $this->assertSame(413, $e->getStatusCode());
            $this->assertStringContainsString('Export exceeds maximum', $e->getMessage());
        }
    }
}
