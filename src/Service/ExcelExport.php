<?php

namespace App\Service;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\AdditionalField;
use App\Util\Compare;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class ExcelExport
{
    private static $customItemFields;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        if (null === self::$customItemFields) {
            $customFieldsArray = $this->getEntityManager()->getRepository(AdditionalField::class)
                ->findBy(['appliesTo' => LsItem::class]);
            self::$customItemFields = array_map(function (AdditionalField $cf) {
                return $cf->getName();
            }, $customFieldsArray);
        }
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

        $items['_'] = ['children' => []];

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
     * Export a CASE file.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generateExcelFile(LsDoc $cfDoc, array $items, array $associations, array $smartLevel, Spreadsheet $phpExcelObject): void
    {
        $licenseTitle = '';
        $licenseText = '';
        $phpExcelObject->getProperties()
            ->setCreator('OpenSALT')
            ->setTitle('case')
        ;

        $license = $cfDoc->getLicence();
        if ($license) {
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

        // add additional fields to worksheet
        $this->setAdditionalFields($activeSheet);

        $j = 2;
        $this->addItemRows($items['_']['children'], $activeSheet, $j, $items, $smartLevel);

        $phpExcelObject->createSheet();
        $activeSheet = $phpExcelObject->setActiveSheetIndex(2);
        $activeSheet
            ->setCellValue('A1', 'identifier')
            ->setCellValue('B1', 'originNodeURI')
            ->setCellValue('C1', 'originNodeIdentifier')
            ->setCellValue('D1', 'originNodeHumanCodingScheme')
            ->setCellValue('E1', 'associationType')
            ->setCellValue('F1', 'destinationNodeURI')
            ->setCellValue('G1', 'destinationNodeIdentifier')
            ->setCellValue('H1', 'destinationNodeHumanCodingScheme')
            ->setCellValue('I1', 'associationGroupIdentifier')
            ->setCellValue('J1', 'associationGroupName')
            ->setTitle('CF Association');

        $j = 2;
        foreach ($associations as $association) {
            $this->addAssociationRow($activeSheet, $j, $association);
            ++$j;
        }

        $phpExcelObject->setActiveSheetIndex(0);
    }

    protected function addItemRows(array $set, Worksheet $activeSheet, int &$j, array $items, array $smartLevel): void
    {
        foreach ($set as $child) {
            $item = $items[$child['id']];
            $this->addItemRow($activeSheet, $j, $item);
            if (array_key_exists($item['id'], $smartLevel)) {
                $activeSheet->setCellValueExplicit(
                    'D'.$j,
                    $smartLevel[$item['id']],
                    DataType::TYPE_STRING
                );
            }
            ++$j;

            if (count($item['children']) > 0) {
                $this->addItemRows($item['children'], $activeSheet, $j, $items, $smartLevel);
            }
        }
    }

    protected function addItemRow(Worksheet $sheet, int $row, array $rowData): void
    {
        $columns = [
            'A' => '[identifier]',
            'B' => '[fullStatement]',
            'C' => '[humanCodingScheme]',
            'D' => '[smartLevel]',
            'E' => '[listEnumInSource]',
            'F' => '[abbreviatedStatement]',
            'G' => '[conceptKeywords]',
            'H' => '[notes]',
            'I' => '[language]',
            'J' => '[educationalAlignment]',
            'K' => '[itemType][title]',
            'L' => '[license]',
        ];

        end($columns);
        $lastCol = key($columns);
        foreach (self::$customItemFields as $customField) {
            $columns[++$lastCol] = sprintf('[extra][customFields][%s]', $customField);
        }

        foreach ($columns as $column => $field) {
            $this->addCellIfExists($sheet, $column, $row, $rowData, $field);
        }
    }

    protected function addAssociationRow(Worksheet $sheet, int $row, array $rowData): void
    {
        $columns = [
            'A' => '[identifier]',
            'B' => '[originNodeURI]',
            'C' => '[originNodeIdentifier]',
            'D' => '[originLsItem][humanCodingScheme]',
            'E' => '[type]',
            'F' => '[destinationNodeUri]',
            'G' => '[destinationNodeIdentifier]',
            'H' => '[destinationLsItem][humanCodingScheme]',
            'I' => '[group][identifier]',
            'J' => '[group][title]',
        ];

        foreach ($columns as $column => $field) {
            $this->addCellIfExists($sheet, $column, $row, $rowData, $field);
        }
    }

    protected function addCellIfExists(Worksheet $sheet, string $col, int $row, array $rowData, string $propertyPath): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        try {
            $value = $propertyAccessor->getValue($rowData, $propertyPath);
        } catch (\Exception $e) {
            // Treat value as unset if the path to it does not work
            return;
        }

        if (null !== $value) {
            $sheet->setCellValue($col.$row, $value);
        }
    }

    protected function setAdditionalFields(Worksheet $sheet): void
    {
        $column = 13;

        if (count(self::$customItemFields) > 0) {
            foreach (self::$customItemFields as $cf) {
                $sheet->setCellValueByColumnAndRow($column, 1, $cf);
                ++$column;
            }
        }
    }
}
