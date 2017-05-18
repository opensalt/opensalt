<?php

namespace Salt\SiteBundle\Service;

use CftfBundle\Entity\LsDoc;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class CaseExport
 *
 * @DI\Service("cftf_export.case")
 */
class CaseExport
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * CreestCsv constructor
     *
     * @param ManagerRegistry $managerRegistry
     *
     * @DI\InjectParams({
     *     "managerRegistry" = @DI\Inject("doctrine")
     * })
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return ObjectManager
     */
    public function getEntityManager(): ObjectManager
    {
        return $this->managerRegistry->getManagerForClass(LsDoc::class);
    }

    /**
     * Export a CASE file
     *
     * @param LsDoc $cfDoc
     * @param array $items
     * @param array $associations
     * @param array $smartLevel
     * @param \PHPExcel $phpExcelObject
     */
    public function exportCaseFile(LsDoc $cfDoc, array $items, array $associations, array $smartLevel, \PHPExcel $phpExcelObject)
    {
        $phpExcelObject->getProperties()
            ->setCreator('OpenSALT')
            ->setTitle('case');

        $phpExcelObject->setActiveSheetIndex(0)
            ->setCellValue('A1', 'identifier')
            ->setCellValue('B1', 'creator')
            ->setCellValue('C1', 'title')
            ->setCellValue('D1', 'lastChangeDate')
            ->setCellValue('E1', 'officialSourceURL')
            ->setCellValue('F1', 'publisher')
            ->setCellValue('G1', 'description')
            ->setCellValue('H1', 'subject')
            ->setCellValue('I1', 'language')
            ->setCellValue('J1', 'version')
            ->setCellValue('K1', 'adoptionStatus')
            ->setCellValue('L1', 'statusStartDate')
            ->setCellValue('M1', 'statusEndDate')
            ->setCellValue('N1', 'license')
            ->setCellValue('O1', 'notes')
            ->setCellValue('A2', $cfDoc->getIdentifier())
            ->setCellValue('B2', $cfDoc->getCreator())
            ->setCellValue('C2', $cfDoc->getTitle())
            ->setCellValue('D2', $cfDoc->getUpdatedAt())
            ->setCellValue('E2', $cfDoc->getOfficialUri())
            ->setCellValue('F2', $cfDoc->getPublisher())
            ->setCellValue('G2', $cfDoc->getDescription())
            ->setCellValue('H2', $cfDoc->getSubject())
            ->setCellValue('I2', $cfDoc->getLanguage())
            ->setCellValue('J2', $cfDoc->getVersion())
            ->setCellValue('K2', $cfDoc->getAdoptionStatus())
            ->setCellValue('L2', $cfDoc->getStatusStart())
            ->setCellValue('M2', $cfDoc->getStatusEnd())
            ->setCellValue('O2', $cfDoc->getNote());

        $phpExcelObject->getActiveSheet()->setTitle('CF Doc');
        $phpExcelObject->createSheet();
        $phpExcelObject->setActiveSheetIndex(1);
        $activeSheet = $phpExcelObject->getActiveSheet();

        $activeSheet
            ->setCellValue('A1', 'identifier')
            ->setCellValue('B1', 'fullStatement')
            ->setCellValue('C1', 'humanCodingScheme')
            ->setCellValue('D1', 'smartLevel')
            ->setCellValue('E1', 'listEnumeration')
            ->setCellValue('F1', 'abbreviatedStatement')
            ->setCellValue('G1', 'conceptKeywords')
            ->setCellValue('H1', 'notes')
            ->setCellValue('I1', 'language')
            ->setCellValue('J1', 'educationLevel')
            ->setCellValue('K1', 'CFItemType')
            ->setCellValue('L1', 'license')
            ->setCellValue('M1', 'lastChangeDateTime')
            ->setTitle('CF Item');

        $j = 2;
        foreach ($items as $item) {
            $this->addItemRow($activeSheet, $j, $item);
            if (array_key_exists($item['id'], $smartLevel)) {
                $activeSheet->setCellValue('D'.$j, $smartLevel[$item['id']]);
            }
            ++$j;
        }

        $phpExcelObject->createSheet();
        $phpExcelObject->setActiveSheetIndex(2);
        $activeSheet = $phpExcelObject->getActiveSheet();
        $activeSheet->setTitle('CF Association');

        $activeSheet
            ->setCellValue('A1', 'identifier')
            ->setCellValue('B1', 'uri')
            ->setCellValue('C1', 'originNodeIdentifier')
            ->setCellValue('D1', 'destinationNodeIdentifier')
            ->setCellValue('E1', 'associationType')
            ->setCellValue('F1', 'associationGroupIdentifier')
            ->setCellValue('G1', 'associationGroupName')
            ->setCellValue('H1', 'lastChangeDateTime');

        $j = 2;
        foreach ($associations as $association) {
            $this->addAssociationRow($activeSheet, $j, $association);
            ++$j;
        }
    }

    /**
     * Add item row to worksheet
     *
     * @param \PHPExcel_Worksheet $sheet
     * @param int $y
     * @param array $row
     */
    protected function addItemRow(\PHPExcel_Worksheet $sheet, int $y, array $row): void
    {
        $columns = [
            'A' => 'identifier',
            'B' => 'fullStatement',
            'C' => 'humanCodingScheme',
            'E' => 'listEnumInSource',
            'F' => 'abbreviatedStatement',
            'G' => 'conceptKeywords',
            'H' => 'notes',
            'I' => 'language',
            'J' => 'educationalAlignment',
            'K' => 'itemType',
            'L' => 'license',
            'M' => 'updatedAt',
        ];

        foreach ($columns as $column => $field) {
            $this->addCellIfExists($sheet, $column, $y, $row, $field);
        }
    }

    /**
     * Add association row to worksheet
     *
     * @param \PHPExcel_Worksheet $sheet
     * @param int $y
     * @param array $row
     */
    protected function addAssociationRow(\PHPExcel_Worksheet $sheet, int $y, array $row): void
    {
        $columns = [
            'A' => 'lsDocIdentifier',
            'B' => 'lsDocUri',
            'C' => 'originNodeIdentifier',
            'D' => 'destinationNodeIdentifier',
            'E' => 'type',
            'F' => 'group',
            'G' => 'groupName',
            'H' => 'updatedAt',
        ];

        foreach ($columns as $column => $field) {
            $this->addCellIfExists($sheet, $column, $y, $row, $field);
        }
    }

    /**
     * Fill in a cell if there is a value
     *
     * @param \PHPExcel_Worksheet $sheet
     * @param string $x
     * @param int $y
     * @param array $row
     * @param string $field
     */
    protected function addCellIfExists(\PHPExcel_Worksheet $sheet, string $x, int $y, array $row, string $field): void
    {
        if (array_key_exists($field, $row)) {
            $sheet->setCellValue($x.$y, $row[$field]);
        }
    }
}
