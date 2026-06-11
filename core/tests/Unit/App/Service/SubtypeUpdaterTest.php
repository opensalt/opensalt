<?php

declare(strict_types=1);

namespace Tests\Unit\App\Service;

use App\Service\SubtypeUpdater;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class SubtypeUpdaterTest extends TestCase
{
    private ManagerRegistry $registry;
    private Connection $connection;
    private EntityManagerInterface $entityManager;
    private string $fixturePath;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->method('getConnection')->willReturn($this->connection);
        $this->registry->method('getManager')->willReturn($this->entityManager);

        $this->fixturePath = sys_get_temp_dir().'/subtype_updater_test_'.uniqid().'.xlsx';
        $this->createTestSpreadsheet();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->fixturePath)) {
            unlink($this->fixturePath);
        }
    }

    public function testLoadSpreadsheetCommitsOnSuccess(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->once())->method('commit');
        $this->connection->expects($this->never())->method('rollBack');
        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('clear');

        $updater = new SubtypeUpdater($this->registry);
        $result = $updater->loadSpreadsheet($this->fixturePath);

        $this->assertIsArray($result);
    }

    public function testLoadSpreadsheetRollsBackOnFailure(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->never())->method('commit');
        $this->connection->expects($this->once())->method('rollBack');
        $this->entityManager->expects($this->once())->method('clear');

        $this->entityManager->method('flush')
            ->willThrowException(new \RuntimeException('DB error'));

        $updater = new SubtypeUpdater($this->registry);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB error');
        $updater->loadSpreadsheet($this->fixturePath);
    }

    public function testLoadSpreadsheetClearsManagerOnFailure(): void
    {
        $this->connection->method('beginTransaction');
        $this->connection->method('rollBack');

        $this->entityManager->method('flush')
            ->willThrowException(new \RuntimeException('fail'));

        $this->entityManager->expects($this->once())->method('clear');

        $updater = new SubtypeUpdater($this->registry);

        try {
            $updater->loadSpreadsheet($this->fixturePath);
        } catch (\RuntimeException) {
            // expected
        }
    }

    public function testLoadSpreadsheetReturnsErrorForMissingFile(): void
    {
        $updater = new SubtypeUpdater($this->registry);
        $result = $updater->loadSpreadsheet('/nonexistent/file.xlsx');

        $this->assertSame([['row' => '', 'msg' => 'Error Loading file']], $result);
    }

    private function createTestSpreadsheet(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue([1, 1], 'Identifier');
        $sheet->setCellValue([8, 1], 'Matched Identifier');
        $sheet->setCellValue([11, 1], 'New Association Type');
        $sheet->setCellValue([12, 1], 'Association Sub-type');
        $sheet->setCellValue([13, 1], 'Association Annotation');

        $sheet->setCellValue([1, 2], 'urn:origin-1');
        $sheet->setCellValue([8, 2], 'urn:dest-1');
        $sheet->setCellValue([11, 2], 'exactMatchOf');
        $sheet->setCellValue([12, 2], 'Identical');
        $sheet->setCellValue([13, 2], 'test annotation');

        $writer = new Xlsx($spreadsheet);
        $writer->save($this->fixturePath);
        $spreadsheet->disconnectWorksheets();
    }
}
