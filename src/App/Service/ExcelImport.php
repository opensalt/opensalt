<?php

namespace App\Service;

use CftfBundle\Entity\ImportLog;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefAssociationGrouping;
use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service()
 */
class ExcelImport
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

    public function importExcel(string $excelFilePath): LsDoc
    {
        $phpExcelObject = \PHPExcel_IOFactory::load($excelFilePath);

        /** @var LsItem[] $items */
        $items = [];
        $itemSmartLevels = [];

        /** @var LsItem[] $smartLevels */
        $smartLevels = [];

        $sheet = $phpExcelObject->getSheetByName('CF Doc');
        $doc = $this->saveDoc($sheet);

        $sheet = $phpExcelObject->getSheetByName('CF Item');
        $lastRow = $sheet->getHighestRow();
        for ($i = 2; $i <= $lastRow; ++$i) {
            $item = $this->saveItem($sheet, $doc, $i);
            $items[$item->getIdentifier()] = $item;
            $smartLevel = (string) $sheet->getCellByColumnAndRow(3, $i)->getValue();
            if (!empty($smartLevel)) {
                $smartLevels[$smartLevel] = $item;
                $itemSmartLevels[$item->getIdentifier()] = $smartLevel;
            }
        }

        foreach ($items as $item) {
            $smartLevel = $itemSmartLevels[$item->getIdentifier()];
            $levels = explode('.', $smartLevel);
            $seq = array_pop($levels);
            $parentLevel = implode('.', $levels);

            if (!is_numeric($seq)) {
                $seq = null;
            }

            if (array_key_exists($parentLevel, $smartLevels)) {
                $smartLevels[$parentLevel]->addChild($item, null, $seq);
            } else {
                $doc->createChildItem($item, null, $seq);
            }
        }

        $sheet = $phpExcelObject->getSheetByName('CF Association');
        $lastRow = $sheet->getHighestRow();
        for ($i = 2; $i <= $lastRow; ++$i) {
            $this->saveAssociation($sheet, $doc, $i, $items);
        }

        return $doc;
    }

    private function saveDoc(\PHPExcel_Worksheet $sheet): LsDoc
    {
        $doc = new LsDoc();
        $doc->setIdentifier($sheet->getCellByColumnAndRow(0, 2)->getValue());
        $doc->setCreator($sheet->getCellByColumnAndRow(1, 2)->getValue());
        $doc->setTitle($sheet->getCellByColumnAndRow(2, 2)->getValue());
        $doc->setOfficialUri($sheet->getCellByColumnAndRow(4, 2)->getValue());
        $doc->setPublisher($sheet->getCellByColumnAndRow(5, 2)->getValue());
        $doc->setDescription($sheet->getCellByColumnAndRow(6, 2)->getValue());
        $doc->setSubject($sheet->getCellByColumnAndRow(7, 2)->getValue());
        $doc->setLanguage($sheet->getCellByColumnAndRow(8, 2)->getValue());
        $doc->setVersion($sheet->getCellByColumnAndRow(9, 2)->getValue());
        if (!empty($sheet->getCellByColumnAndRow(10, 2)->getValue())) {
            $doc->setAdoptionStatus($sheet->getCellByColumnAndRow(10, 2)->getValue());
        }
        $doc->setStatusStart(
            new \DateTime(
                \PHPExcel_Style_NumberFormat::toFormattedString(
                    $sheet->getCellByColumnAndRow(11, 2)->getValue(),
                    'YYYY-MM-DD'
                )
            )
        );
        $doc->setStatusEnd(
            new \DateTime(
                \PHPExcel_Style_NumberFormat::toFormattedString(
                    $sheet->getCellByColumnAndRow(12, 2)->getValue(),
                    'YYYY-MM-DD'
                )
            )
        );
        $doc->setLicence($sheet->getCellByColumnAndRow(13, 2)->getValue());
        $doc->setNote($sheet->getCellByColumnAndRow(14, 2)->getValue());

        $this->getEntityManager()->persist($doc);

        return $doc;
    }

    private function saveItem(\PHPExcel_Worksheet $sheet, LsDoc $doc, int $row): LsItem
    {
        static $itemTypes = [];

        $identifier = $sheet->getCellByColumnAndRow(0, $row)->getValue();
        if (empty($identifier)) {
            $identifier = null;
        }
        $item = $doc->createItem($identifier);

        $itemTypeTitle = $sheet->getCellByColumnAndRow(10, $row)->getValue();

        if (in_array($itemTypeTitle, $itemTypes, true)) {
            $itemType = $itemTypes[$itemTypeTitle];
        } else {
            $itemType = $this->getEntityManager()->getRepository(LsDefItemType::class)
                ->findOneByTitle($itemTypeTitle);

            if (null === $itemType && !empty($itemTypeTitle)) {
                $itemType = new LsDefItemType();
                $itemType->setTitle($itemTypeTitle);
                $itemType->setCode($itemTypeTitle);
                $itemType->setHierarchyCode($sheet->getCellByColumnAndRow(3, $row)->getValue());
                $this->getEntityManager()->persist($itemType);
            }

            $itemTypes[$itemTypeTitle] = $itemType;
        }
        $item->setItemType($itemType);

        $item->setFullStatement($sheet->getCellByColumnAndRow(1, $row)->getValue());
        $item->setHumanCodingScheme($sheet->getCellByColumnAndRow(2, $row)->getValue());
        $item->setListEnumInSource($sheet->getCellByColumnAndRow(4, $row)->getValue());
        $item->setAbbreviatedStatement($sheet->getCellByColumnAndRow(5, $row)->getValue());
        $item->setConceptKeywords($sheet->getCellByColumnAndRow(6, $row)->getValue());
        $item->setNotes($sheet->getCellByColumnAndRow(7, $row)->getValue());
        $item->setLanguage($sheet->getCellByColumnAndRow(8, $row)->getValue());
        $item->setEducationalAlignment($sheet->getCellByColumnAndRow(9, $row)->getValue());

        $this->getEntityManager()->persist($item);

        return $item;
    }

    private function saveAssociation(\PHPExcel_Worksheet $sheet, LsDoc $doc, int $row, array $items): ?LsAssociation
    {
        $fieldNames = [
            0 => 'identifier',
            1 => 'uri',
            2 => 'originNodeIdentifier',
            3 => 'destinationNodeIdentifier',
            4 => 'associationType',
            5 => 'associationGroupIdentifier',
            6 => 'associationGroupName',
            7 => 'lastChangeDateTime',
        ];

        $fields = [];
        foreach ($fieldNames as $col => $name) {
            $fields[$name] = $sheet->getCellByColumnAndRow($col, $row)->getValue();
        }

        if (empty($fields['identifier'])) {
            $fields['identifier'] = null;
        }

        $association = $doc->createAssociation($fields['identifier']);

        $association->setUri($fields['uri']);

        if (array_key_exists((string) $fields['originNodeIdentifier'], $items)) {
            $association->setOrigin($items[$fields['originNodeIdentifier']]);
        } else {
            $ref = 'data:text/x-ref-unresolved,'.$fields['originNodeIdentifier'];
            $association->setOrigin($ref, $fields['originNodeIdentifier']);
        }

        if (array_key_exists((string) $fields['destinationNodeIdentifier'], $items)) {
            $association->setDestination($items[$fields['destinationNodeIdentifier']]);
        } else {
            $ref = 'data:text/x-ref-unresolved,'.$fields['destinationNodeIdentifier'];
            $association->setDestination($ref, $fields['destinationNodeIdentifier']);
        }

        $associationType = ucfirst(preg_replace('/([A-Z])/', ' $1', (string) $fields['associationType']));
        if (in_array($associationType, LsAssociation::allTypes(), true)) {
            $association->setType($associationType);
        } else {
            $log = new ImportLog();
            $log->setLsDoc($doc);
            $log->setMessageType('error');
            $log->setMessage("Invalid Association Type ({$associationType} on row {$row}.");

            return null;
        }

        if (!empty($fields['associationGroupIdentifier'])) {
            $associationGrouping = new LsDefAssociationGrouping();
            $associationGrouping->setLsDoc($doc);
            $associationGrouping->setTitle($fields['associationGroupName']);
            $association->setGroup($associationGrouping);
            $this->getEntityManager()->persist($associationGrouping);
        }

        $this->getEntityManager()->persist($association);

        return $association;
    }
}
