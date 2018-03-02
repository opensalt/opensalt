<?php

namespace App\Service;

use App\Entity\Framework\ImportLog;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use JMS\DiExtraBundle\Annotation as DI;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
        $phpExcelObject = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFilePath);

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
            $smartLevel = (string) $this->getCellValueOrNull($sheet, 3, $i);
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

    private function saveDoc(Worksheet $sheet): LsDoc
    {
        $doc = new LsDoc();
        $doc->setIdentifier($this->getCellValueOrNull($sheet, 1, 2));
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

        $licence = $this->getLicence($sheet);

        $doc->setLicence($licence);

        $doc->setNote($this->getCellValueOrNull($sheet, 17, 2));

        $this->getEntityManager()->persist($doc);

        return $doc;
    }

    private function getLicence(Worksheet $sheet): LsDefLicence
    {
        $uri = $this->getCellValueOrNull($sheet, 14, 2);
        $title = $this->getCellValueOrNull($sheet, 15, 2);
        $licenceText = $this->getCellValueOrNull($sheet, 16, 2);

        // Look for this licence locally
        $licence = $this->getEntityManager()->getRepository(LsDefLicence::class)
            ->findOneByUri($uri);

        // Check if the licence uri exists locally
        if (null !== $licence && !empty($uri)) {
            return $licence;
        }

        // creates licence if it doesn't exists locally
        $licence = new LsDefLicence();

        $licence->setUri($uri);
        $licence->setTitle($title);
        $licence->setLicenceText($licenceText);

        $this->getEntityManager()->persist($licence);

        return $licence;
    }

    private function saveItem(Worksheet $sheet, LsDoc $doc, int $row): LsItem
    {
        static $itemTypes = [];

        $identifier = $this->getCellValueOrNull($sheet, 1, $row);
        if (empty($identifier)) {
            $identifier = null;
        }
        $item = $doc->createItem($identifier);

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
        // col 13 - last change date time

        $this->getEntityManager()->persist($item);

        return $item;
    }

    private function saveAssociation(Worksheet $sheet, LsDoc $doc, int $row, array $items): ?LsAssociation
    {
        $fieldNames = [
            1 => 'identifier',
            2 => 'uri',
            3 => 'originNodeIdentifier',
            // 4 => 'originNodeUri',
            5 => 'destinationNodeIdentifier',
            // 6 => 'destinationNodeUri',
            7 => 'associationType',
            8 => 'associationGroupIdentifier',
            9 => 'associationGroupName',
            10 => 'lastChangeDateTime',
        ];

        $fields = [];
        foreach ($fieldNames as $col => $name) {
            $fields[$name] = $this->getCellValueOrNull($sheet, $col, $row);
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

    private function getCellValueOrNull(Worksheet $sheet, int $col, int $row)
    {
        $cell = $sheet->getCellByColumnAndRow($col, $row);

        if (null === $cell) {
            return null;
        }

        return $cell->getValue();
    }
}
