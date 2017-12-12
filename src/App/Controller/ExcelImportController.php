<?php

namespace App\Controller;

use App\Command\CommandDispatcher;
use CftfBundle\Entity\ImportLog;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefAssociationGrouping;
use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExcelImportController extends Controller
{
    use CommandDispatcher;

    public function __construct(ContainerInterface $container = null)
    {
        // event_dispatcher
        $this->setContainer($container);
    }

    /**
     * @Route("/salt/excel/import", name="import_excel_file")
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return Response
     */
    public function importExcelAction(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $file = $request->files->get('file');
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject($file->getRealPath());
        /** @var LsItem[] $items */
        $items = [];
        $itemSmartLevels = [];
        /** @var LsItem[] $smartLevels */
        $smartLevels = [];

        $sheet = $phpExcelObject->getSheetByName('CF Doc');
        $lsDoc = $this->saveDoc($sheet, $em);

        $sheet = $phpExcelObject->getSheetByName('CF Item');
        $lastRow = $sheet->getHighestRow();
        for ($i = 2; $i <= $lastRow; ++$i) {
            $lsItem = $this->saveItem($sheet, $lsDoc, $i, $em);
            $items[$lsItem->getIdentifier()] = $lsItem;
            $smartLevel = (string) $sheet->getCellByColumnAndRow(3, $i)->getValue();
            if (!empty($smartLevel)) {
                $smartLevels[$smartLevel] = $lsItem;
                $itemSmartLevels[$lsItem->getIdentifier()] = $smartLevel;
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
                $lsDoc->createChildItem($item, null, $seq);
            }
        }

        $sheet = $phpExcelObject->getSheetByName('CF Association');
        $lastRow = $sheet->getHighestRow();
        for ($i = 2; $i <= $lastRow; ++$i) {
            $this->saveAssociation($sheet, $lsDoc, $i, $em, $items);
        }

        $em->flush();

        return new Response('OK', Response::HTTP_OK);
    }

    private function saveDoc(\PHPExcel_Worksheet $sheet, ObjectManager $em): LsDoc
    {
        $lsDoc = new LsDoc();
        $lsDoc->setIdentifier($sheet->getCellByColumnAndRow(0, 2)->getValue());
        $lsDoc->setCreator($sheet->getCellByColumnAndRow(1, 2)->getValue());
        $lsDoc->setTitle($sheet->getCellByColumnAndRow(2, 2)->getValue());
        $lsDoc->setOfficialUri($sheet->getCellByColumnAndRow(4, 2)->getValue());
        $lsDoc->setPublisher($sheet->getCellByColumnAndRow(5, 2)->getValue());
        $lsDoc->setDescription($sheet->getCellByColumnAndRow(6, 2)->getValue());
        $lsDoc->setSubject($sheet->getCellByColumnAndRow(7, 2)->getValue());
        $lsDoc->setLanguage($sheet->getCellByColumnAndRow(8, 2)->getValue());
        $lsDoc->setVersion($sheet->getCellByColumnAndRow(9, 2)->getValue());
        if (!empty($sheet->getCellByColumnAndRow(10, 2)->getValue())) {
            $lsDoc->setAdoptionStatus($sheet->getCellByColumnAndRow(10, 2)->getValue());
        }
        $lsDoc->setStatusStart($sheet->getCellByColumnAndRow(11, 2)->getValue());
        $lsDoc->setStatusEnd($sheet->getCellByColumnAndRow(12, 2)->getValue());
        $lsDoc->setLicence($sheet->getCellByColumnAndRow(13, 2)->getValue());
        $lsDoc->setNote($sheet->getCellByColumnAndRow(14, 2)->getValue());

        $em->persist($lsDoc);

        return $lsDoc;
    }

    private function saveItem(\PHPExcel_Worksheet $sheet, LsDoc $lsDoc, int $row, ObjectManager $em): LsItem
    {
        static $itemTypes = [];

        $identifier = $sheet->getCellByColumnAndRow(0, $row)->getValue();
        if (empty($identifier)) {
            $identifier = null;
        }
        $lsItem = $lsDoc->createItem($identifier);

        $itemTypeTitle = $sheet->getCellByColumnAndRow(10, $row)->getValue();

        if (in_array($itemTypeTitle, $itemTypes, true)) {
            $itemType = $itemTypes[$itemTypeTitle];
        } else {
            $itemType = $em->getRepository('CftfBundle:LsDefItemType')
                ->findOneByTitle($itemTypeTitle);

            if (null === $itemType && !empty($itemTypeTitle)) {
                $itemType = new LsDefItemType();
                $itemType->setTitle($itemTypeTitle);
                $itemType->setCode($itemTypeTitle);
                $itemType->setHierarchyCode($sheet->getCellByColumnAndRow(3, $row)->getValue());
                $em->persist($itemType);
            }

            $itemTypes[$itemTypeTitle] = $itemType;
        }
        $lsItem->setItemType($itemType);

        $lsItem->setFullStatement($sheet->getCellByColumnAndRow(1, $row)->getValue());
        $lsItem->setHumanCodingScheme($sheet->getCellByColumnAndRow(2, $row)->getValue());
        $lsItem->setListEnumInSource($sheet->getCellByColumnAndRow(4, $row)->getValue());
        $lsItem->setAbbreviatedStatement($sheet->getCellByColumnAndRow(5, $row)->getValue());
        $lsItem->setConceptKeywords($sheet->getCellByColumnAndRow(6, $row)->getValue());
        $lsItem->setNotes($sheet->getCellByColumnAndRow(7, $row)->getValue());
        $lsItem->setLanguage($sheet->getCellByColumnAndRow(8, $row)->getValue());
        $lsItem->setEducationalAlignment($sheet->getCellByColumnAndRow(9, $row)->getValue());

        $em->persist($lsItem);

        return $lsItem;
    }

    private function saveAssociation(\PHPExcel_Worksheet $sheet, LsDoc $lsDoc, int $row, ObjectManager $em, array $items): ?LsAssociation
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

        $lsAssociation = $lsDoc->createAssociation($fields['identifier']);

        $lsAssociation->setUri($fields['uri']);

        if (array_key_exists((string) $fields['originNodeIdentifier'], $items)) {
            $lsAssociation->setOrigin($items[$fields['originNodeIdentifier']]);
        } else {
            $ref = 'data:text/x-ref-unresolved,'.$fields['originNodeIdentifier'];
            $lsAssociation->setOrigin($ref, $fields['originNodeIdentifier']);
        }

        if (array_key_exists((string) $fields['destinationNodeIdentifier'], $items)) {
            $lsAssociation->setDestination($items[$fields['destinationNodeIdentifier']]);
        } else {
            $ref = 'data:text/x-ref-unresolved,'.$fields['destinationNodeIdentifier'];
            $lsAssociation->setDestination($ref, $fields['destinationNodeIdentifier']);
        }

        $associationType = ucfirst(preg_replace('/([A-Z])/', ' $1', (string) $fields['associationType']));
        if (in_array($associationType, LsAssociation::allTypes(), true)) {
            $lsAssociation->setType($associationType);
        } else {
            $log = new ImportLog();
            $log->setLsDoc($lsDoc);
            $log->setMessageType('error');
            $log->setMessage("Invalid Association Type ({$associationType} on row {$row}.");

            return null;
        }

        if (!empty($fields['associationGroupIdentifier'])) {
            $lsDefAssocGroup = new LsDefAssociationGrouping();
            $lsDefAssocGroup->setLsDoc($lsDoc);
            $lsDefAssocGroup->setTitle($fields['associationGroupName']);
            $lsAssociation->setGroup($lsDefAssocGroup);
            $em->persist($lsDefAssocGroup);
        }

        $em->persist($lsAssociation);

        return $lsAssociation;
    }
}
