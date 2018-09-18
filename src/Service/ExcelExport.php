<?php

namespace App\Service;

use App\Entity\Framework\LsDoc;
use App\Util\Compare;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelExport
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function exportExcelFile(LsDoc $doc): Spreadsheet
    {
        $repo = $this->getEntityManager()->getRepository(LsDoc::class);

        $items = $repo->findAllChildrenArray($doc);
        $topChildren = $repo->findTopChildrenIds($doc);
        $associations = $repo->findAllAssociations($doc);

        $smartLevel = [];

        $items['_'] = ['children'=>[]];

        $i = 0;
        foreach ($topChildren as $id) {
            $smartLevel[$id] = ++$i;
            $item = $items[$id];

            if (count($item['children']) > 0) {
                $this->getSmartLevel($item['children'], $id, $items, $smartLevel);
            }

            $items['_']['children'][] = $items[$id];
        }
        Compare::sortArrayByFields($items['_']['children'], ['sequenceNumber', 'listEnumInSource', 'humanCodingScheme']);

        $phpExcelObject = new Spreadsheet();

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
     * @param Spreadsheet $phpExcelObject
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generateExcelFile(LsDoc $cfDoc, array $items, array $associations, array $smartLevel, Spreadsheet $phpExcelObject): void
    {
        $licenseTitle = '';
        $licenseText = '';
        $licenseUri = '';
        $phpExcelObject->getProperties()
            ->setCreator('OpenSALT')
            ->setTitle('case');

        $license = $cfDoc->getLicence();
        if($license){
            $licenseUri = $license->getUri();
            $licenseTitle = $license->getTitle();
            $licenseText = $license->getLicenceText();
        }

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
            ->setCellValue('N1', 'licenseTitle')
            ->setCellValue('O1', 'licenseText')
            ->setCellValue('P1', 'notes')
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
            ->setCellValue('N2', $licenseTitle)
            ->setCellValue('O2', $licenseText)
            ->setCellValue('P2', $cfDoc->getNote())
            ->setTitle('CF Doc');

        $phpExcelObject->createSheet();
        $activeSheet = $phpExcelObject->setActiveSheetIndex(1);
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
            ->setTitle('CF Item');

        $j = 2;
        $this->addItemRows($items['_']['children'], $activeSheet, $j, $smartLevel);

        $phpExcelObject->createSheet();
        $activeSheet = $phpExcelObject->setActiveSheetIndex(2);
        $activeSheet
            ->setCellValue('A1', 'identifier')
            ->setCellValue('B1', 'originNodeIdentifier')
            ->setCellValue('C1', 'originNodeHumanCodingScheme')
            ->setCellValue('D1', 'associationType')
            ->setCellValue('E1', 'destinationNodeHumanCodingScheme')
            ->setCellValue('F1', 'destinationNodeIdentifier')
            ->setCellValue('G1', 'associationGroupIdentifier')
            ->setCellValue('H1', 'associationGroupName')
            ->setTitle('CF Association');

        $j = 2;
        foreach ($associations as $association) {
            $this->addAssociationRow($activeSheet, $j, $association);
            ++$j;
        }
    }

    protected function addItemRows(array $set, Worksheet $activeSheet, int &$j, array $smartLevel): void
    {
        foreach ($set as $item) {
            $this->addItemRow($activeSheet, $j, $item);
            if (array_key_exists($item['id'], $smartLevel)) {
                $activeSheet->setCellValueExplicit(
                    'D'.$j,
                    $smartLevel[$item['id']],
                    DataType::TYPE_STRING
                );
            }
            ++$j;

            if (!empty($item['children'])) {
                $this->addItemRows($item['children'], $activeSheet, $j, $smartLevel);
            }
        }
    }

    /**
     * Add item row to worksheet
     *
     * @param Worksheet $sheet
     * @param int $y
     * @param array $row
     */
    protected function addItemRow(Worksheet $sheet, int $y, array $row): void
    {
        $columns = [
            'A' => 'identifier',
            'B' => 'fullStatement',
            'C' => 'humanCodingScheme',
            'D' => 'smartLevel',
            'E' => 'listEnumInSource',
            'F' => 'abbreviatedStatement',
            'G' => 'conceptKeywords',
            'H' => 'notes',
            'I' => 'language',
            'J' => 'educationalAlignment',
            'K' => ['itemType', 'title'],
            'L' => 'license',
        ];

        foreach ($columns as $column => $field) {
            $this->addCellIfExists($sheet, $column, $y, $row, $field);
        }
    }

    /**
     * Add association row to worksheet
     *
     * @param Worksheet $sheet
     * @param int $y
     * @param array $row
     */
    protected function addAssociationRow(Worksheet $sheet, int $y, array $row): void
    {
        $columns = [
            'A' => 'identifier',
            'B' => 'originNodeIdentifier',
            'C' => ['originLsItem', 'humanCodingScheme'],
            'D' => 'type',
            'E' => ['destinationLsItem', 'humanCodingScheme'],
            'F' => 'destinationNodeIdentifier',
            'G' => 'group',
            'H' => 'groupName',
        ];

        foreach ($columns as $column => $field) {
            $this->addCellIfExists($sheet, $column, $y, $row, $field);
        }
    }

    /**
     * Fill in a cell if there is a value
     *
     * @param Worksheet $sheet
     * @param string $x
     * @param int $y
     * @param array $row
     * @param string|array $field
     */
    protected function addCellIfExists(Worksheet $sheet, string $x, int $y, array $row, $field): void
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
