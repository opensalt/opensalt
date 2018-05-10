<?php

namespace App\Service;

use App\Entity\Framework\ImportLog;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Ramsey\Uuid\Uuid;

class ExcelImport
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

    public function importExcel(string $excelFilePath): LsDoc
    {
        $phpExcelObject = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFilePath);

        /** @var LsItem[] $items */
        $items = [];
        $itemSmartLevels = [];
        $associations = [];
        $children = [];

        /** @var LsItem[] $smartLevels */
        $smartLevels = [];

        $sheet = $phpExcelObject->getSheetByName('CF Doc');
        $doc = $this->saveDoc($sheet);

        $sheet = $phpExcelObject->getSheetByName('CF Item');
        $lastRow = $sheet->getHighestRow();

        for ($i = 2; $i <= $lastRow; ++$i) {
            $item = $this->saveItem($sheet, $doc, $i);
            if ($item !== null) $items[$item->getIdentifier()] = $item;
            $smartLevel = (string) $this->getCellValueOrNull($sheet, 4, $i);

            if (!empty($smartLevel)) {
                $smartLevels[$smartLevel] = $item;
                if ($item !== null) $itemSmartLevels[$item->getIdentifier()] = $smartLevel;
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
                $children[$item->getIdentifier()] = $doc->getIdentifier();
            } else {

                $assoc = $this->getEntityManager()->getRepository(LsAssociation::class)->findOneBy([
                    'originLsItem' => $item,
                    'type' => LsAssociation::CHILD_OF,
                    'lsDoc' => $item->getLsDoc(),
                ]);

                if (null === $assoc) {
                    $doc->createChildItem($item, null, $seq);
                    $children[$item->getIdentifier()] = $doc->getIdentifier();
                } else {
                    $assoc->setSequenceNumber($seq);
                }
            }
        }

        $sheet = $phpExcelObject->getSheetByName('CF Association');
        $lastRow = $sheet->getHighestRow();
        for ($i = 2; $i <= $lastRow; ++$i) {
            $association = $this->saveAssociation($sheet, $doc, $i, $items, $children);
            if (null !== $association) {
                $associations[$association->getIdentifier()] = $association;
            }
        }

        $this->checkRemovedElements($doc, $items, 'items');
        $this->checkRemovedElements($doc, $associations, 'associations');

        return $doc;
    }

    private function saveDoc(Worksheet $sheet): LsDoc
    {
        $docRepo = $this->getEntityManager()->getRepository(LsDoc::class);
        $doc = $docRepo->findOneByIdentifier($this->getCellValueOrNull($sheet, 1, 2));

        if ($doc !== null) {
            /* $doc->setIdentifier($this->getCellValueOrNull($sheet, 1, 2)); */
            $doc->setCreator($this->getCellValueOrNull($sheet, 2, 2));
            $doc->setTitle($this->getCellValueOrNull($sheet, 3, 2));
            // col 4 - lastChangeDate
            $doc->setOfficialUri($this->getCellValueOrNull($sheet, 5, 2));
            $doc->setPublisher($this->getCellValueOrNull($sheet, 6, 2));
            $doc->setDescription($this->getCellValueOrNull($sheet, 7, 2));
            $doc->setSubject($this->getCellValueOrNull($sheet, 8, 2));
            $doc->setLanguage($this->getCellValueOrNull($sheet, 9, 2));
            $doc->setVersion($this->getCellValueOrNull($sheet, 10, 2));
            if (!empty($this->getCellValueOrNull($sheet, 11, 2))) {
                $doc->setAdoptionStatus($this->getCellValueOrNull($sheet, 11, 2));
            }
            $doc->setStatusStart(
                new \DateTime(
                    \PhpOffice\PhpSpreadsheet\Style\NumberFormat::toFormattedString(
                        $this->getCellValueOrNull($sheet, 12, 2),
                        'YYYY-MM-DD'
                    )
                )
            );
            $doc->setStatusEnd(
                new \DateTime(
                    \PhpOffice\PhpSpreadsheet\Style\NumberFormat::toFormattedString(
                        $this->getCellValueOrNull($sheet, 13, 2),
                        'YYYY-MM-DD'
                    )
                )
            );

            if ($this->getCellValueOrNull($sheet, 14, 2) !== null && $this->getCellValueOrNull($sheet, 15, 2) !== null) {
                $licence = $this->getLicence($sheet);
                $doc->setLicence($licence);
            }

            $doc->setNote($this->getCellValueOrNull($sheet, 16, 2));

            $this->getEntityManager()->persist($doc);

            return $doc;
        }

        return null;
    }

    private function getLicence(Worksheet $sheet): LsDefLicence
    {
        $title = $this->getCellValueOrNull($sheet, 14, 2);
        $licenceText = $this->getCellValueOrNull($sheet, 15, 2);

        // creates licence if it doesn't exists locally
        $licence = new LsDefLicence();

        $licence->setTitle($title);
        $licence->setLicenceText($licenceText);

        $this->getEntityManager()->persist($licence);

        return $licence;
    }

    private function saveItem(Worksheet $sheet, LsDoc $doc, int $row): ?LsItem
    {
        static $itemTypes = [];
        $item = null;

        $identifier = $this->getCellValueOrNull($sheet, 1, $row);
        if (empty($identifier)) {
            $identifier = null;
        } elseif (Uuid::isValid($identifier)) {
            $item = $this->getEntityManager()->getRepository(LsItem::class)
                ->findOneBy(['identifier' => $identifier, 'lsDocIdentifier' => $doc->getIdentifier()]);
        }

        if ($item === null && !empty($this->getCellValueOrNull($sheet, 2, $row))) {
            $item = $doc->createItem($identifier);
        }

        if ($item !== null) {
            $itemTypeTitle = $this->getCellValueOrNull($sheet, 11, $row);

            if (in_array($itemTypeTitle, $itemTypes, true)) {
                $itemType = $itemTypes[$itemTypeTitle];
            } else {
                $itemType = $this->getEntityManager()->getRepository(LsDefItemType::class)
                    ->findOneByTitle($itemTypeTitle);

                if (null === $itemType && !empty($itemTypeTitle)) {
                    $itemType = new LsDefItemType();
                    $itemType->setTitle($itemTypeTitle);
                    $itemType->setCode($itemTypeTitle);
                    $itemType->setHierarchyCode($itemTypeTitle);
                    $this->getEntityManager()->persist($itemType);
                }

                $itemTypes[$itemTypeTitle] = $itemType;
            }

            $item->setItemType($itemType);

            $item->setFullStatement($this->getCellValueOrNull($sheet, 2, $row));
            $item->setHumanCodingScheme($this->getCellValueOrNull($sheet, 3, $row));
            // col 4 - smart level
            $item->setListEnumInSource($this->getCellValueOrNull($sheet, 5, $row));
            $item->setAbbreviatedStatement($this->getCellValueOrNull($sheet, 6, $row));
            $item->setConceptKeywords($this->getCellValueOrNull($sheet, 7, $row));
            $item->setNotes($this->getCellValueOrNull($sheet, 8, $row));
            $item->setLanguage($this->getCellValueOrNull($sheet, 9, $row));
            $item->setEducationalAlignment($this->getCellValueOrNull($sheet, 10, $row));
            // col 11 - item type
            // col 12 - licence

            $this->getEntityManager()->persist($item);
        }

        return $item;
    }

    private function saveAssociation(Worksheet $sheet, LsDoc $doc, int $row, array $items, array $children): ?LsAssociation
    {
        $fieldNames = [
            1 => 'identifier',
            2 => 'originNodeIdentifier',
            4 => 'associationType',
            6 => 'destinationNodeIdentifier',
            7 => 'associationGroupIdentifier',
            8 => 'associationGroupName',
        ];

        $itemRepo = $this->getEntityManager()->getRepository(LsItem::class);
        $association = null;
        $fields = [];

        foreach ($fieldNames as $col => $name) {
            $fields[$name] = $this->getCellValueOrNull($sheet, $col, $row);
        }

        if (!array_key_exists((string) $fields['originNodeIdentifier'], $children)) {

            if (empty($fields['identifier'])) {
                $fields['identifier'] = null;
            } elseif (Uuid::isValid($fields['identifier'])) {
                $association = $this->getEntityManager()->getRepository(LsAssociation::class)
                    ->findOneBy(['identifier' => $fields['identifier'], 'lsDocIdentifier' => $doc->getIdentifier()]);
            }

            if ($association === null) {
                $assoc = $this->getEntityManager()->getRepository(LsAssociation::class)->findOneBy([
                    'originNodeIdentifier' => $fields['originNodeIdentifier'],
                    'type' => $fields['associationType'],
                    'destinationNodeIdentifier' => $fields['destinationNodeIdentifier']
                ]);

                if (null === $assoc) {
                    $association = $doc->createAssociation($fields['identifier']);
                }
            }

            if (array_key_exists((string) $fields['originNodeIdentifier'], $items)) {
                $association->setOrigin($items[$fields['originNodeIdentifier']]);
            } else {
                $ref = 'data:text/x-ref-unresolved,'.$fields['originNodeIdentifier'];
                $association->setOrigin($ref, $fields['originNodeIdentifier']);
            }

            if (array_key_exists((string) $fields['destinationNodeIdentifier'], $items)) {
                $association->setDestination($items[$fields['destinationNodeIdentifier']]);
            } elseif ($item = $itemRepo->findOneByIdentifier($fields['destinationNodeIdentifier'])) {
                $items[$item->getIdentifier()] = $item;
                $association->setDestination($item);
            } else {
                $ref = 'data:text/x-ref-unresolved,'.$fields['destinationNodeIdentifier'];
                $association->setDestination($ref, $fields['destinationNodeIdentifier']);
            }

            $allTypes = [];
            foreach(LsAssociation::allTypes() as $type) {
                $allTypes[] = str_replace(' ', '', strtolower($type));
            }

            $associationType = str_replace(' ', '', strtolower($fields['associationType']));

            if (in_array($associationType, $allTypes, true)) {
                $association->setType($fields['associationType']);
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
        }

        return $association;
    }

    private function getCellValueOrNull(Worksheet $sheet, int $col, int $row)
    {
        $cell = $sheet->getCellByColumnAndRow($col, $row);

        if (null === $cell) {
            return null;
        }

        return $cell->getValue();
    }

    private function checkRemovedElements($doc, $array, $type)
    {
        $docRepo = $this->getEntityManager()->getRepository(LsDoc::class);
        $repo = $this->getEntityManager()->getRepository(LsItem::class);
        $findAll = 'findAllItems';
        $remove = 'removeItemAndChildren';

        if ($type === 'associations') {
            $repo = $this->getEntityManager()->getRepository(LsAssociation::class);
            $findAll = 'findAllAssociations';
            $remove = 'removeAssociation';
        }

        $existingItems = $docRepo->$findAll($doc);

        foreach ($existingItems as $existingItem) {
            if (!array_key_exists($existingItem['identifier'], $array)) {
                $element = $repo->findOneByIdentifier($existingItem['identifier']);
                $repo->$remove($element);
            }
        }
    }
}
