<?php

namespace App\Service;

use CftfBundle\Entity\LsDoc;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class ExcelExport
 *
 * @DI\Service()
 */
class ExcelExport
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

    public function exportExcelFile(LsDoc $doc): \PHPExcel
    {
        $repo = $this->getEntityManager()->getRepository(LsDoc::class);

        $items = $repo->findAllChildrenArray($doc);
        $topChildren = $repo->findTopChildrenIds($doc);
        $associations = $repo->findAllAssociations($doc);

        $smartLevel = [];

        $i = 0;
        foreach ($topChildren as $id) {
            $smartLevel[$id] = ++$i;
            $item = $items[$id];

            if (count($item['children']) > 0) {
                $this->getSmartLevel($item['children'], $id, $items, $smartLevel);
            }
        }

        $phpExcelObject = new \PHPExcel();

        $this->generateExcelFile($doc, $items, $associations, $smartLevel, $phpExcelObject);

        return $phpExcelObject;
    }

    protected function getSmartLevel(array $items, $parentId, array $itemsArray, array &$smartLevel): void
    {
        $j = 1;

        foreach ($items as $item) {
            $item = $itemsArray[$item['id']];
            $smartLevel[$item['id']] = $smartLevel[$parentId].'.'.$j;

            if (count($item['children']) > 0) {
                $this->getSmartLevel($item['children'], $item['id'], $itemsArray, $smartLevel);
            }

            ++$j;
        }
    }

    /**
     * Export a CASE file
     *
     * @param LsDoc $cfDoc
     * @param array $items
     * @param array $associations
     * @param array $smartLevel
     * @param \PHPExcel $phpExcelObject
     *
     * @throws \PHPExcel_Exception
     */
    public function generateExcelFile(LsDoc $cfDoc, array $items, array $associations, array $smartLevel, \PHPExcel $phpExcelObject): void
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
                $activeSheet->setCellValueExplicit(
                    'D'.$j,
                    $smartLevel[$item['id']],
                    \PHPExcel_Cell_DataType::TYPE_STRING
                );
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
            ->setCellValue('D1', 'originNodeUri')
            ->setCellValue('E1', 'destinationNodeIdentifier')
            ->setCellValue('F1', 'destinationNodeUri')
            ->setCellValue('G1', 'associationType')
            ->setCellValue('H1', 'associationGroupIdentifier')
            ->setCellValue('I1', 'associationGroupName')
            ->setCellValue('J1', 'lastChangeDateTime');

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
            'K' => ['itemType', 'title'],
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
            'A' => 'identifier',
            'B' => 'uri',
            'C' => 'originNodeIdentifier',
            'D' => 'originNodeUri',
            'E' => 'destinationNodeIdentifier',
            'F' => 'destinationNodeUri',
            'G' => 'type',
            'H' => 'group',
            'I' => 'groupName',
            'J' => 'updatedAt',
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
     * @param string|array $field
     */
    protected function addCellIfExists(\PHPExcel_Worksheet $sheet, string $x, int $y, array $row, $field): void
    {
        if (is_array($field)) {
            if (array_key_exists($field[0], $row) && null !== $row[$field[0]]) {
                $row = $row[$field[0]];
                $field = $field[1];
            } else {
                return;
            }
        }
        if (array_key_exists($field, $row) && null !== $row[$field]) {
            $sheet->setCellValue($x.$y, $row[$field]);
        }
    }
}
