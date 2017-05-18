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
     * @param PHPExcel $phpExcelObject
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
            if (array_key_exists('identifier', $item)) {
                $activeSheet->setCellValue('A'.$j, $item['identifier']);
            }
            if (array_key_exists('fullStatement', $item)) {
                $activeSheet->setCellValue('B'.$j, $item['fullStatement']);
            }
            if (array_key_exists('humanCodingScheme', $item)) {
                $activeSheet->setCellValue('C'.$j, $item['humanCodingScheme']);
            }
            if (array_key_exists($item['id'], $smartLevel)) {
                $activeSheet->setCellValue('D'.$j, $smartLevel[$item['id']]);
            }
            if (array_key_exists('listEnumInSource', $item)) {
                $activeSheet->setCellValue('E'.$j, $item['listEnumInSource']);
            }
            if (array_key_exists('abbreviatedStatement', $item)) {
                $activeSheet->setCellValue('F'.$j, $item['abbreviatedStatement']);
            }
            if (array_key_exists('conceptKeywords', $item)) {
                $activeSheet->setCellValue('G'.$j, $item['conceptKeywords']);
            }
            if (array_key_exists('notes', $item)) {
                $activeSheet->setCellValue('H'.$j, $item['notes']);
            }
            if (array_key_exists('language', $item)) {
                $activeSheet->setCellValue('I'.$j, $item['language']);
            }
            if (array_key_exists('educationalAlignment', $item)) {
                $activeSheet->setCellValue('J'.$j, $item['educationalAlignment']);
            }
            if (array_key_exists('itemType', $item)) {
                $activeSheet->setCellValue('K'.$j, $item['itemType']);
            }
            if (array_key_exists('license', $item)) {
                $activeSheet->setCellValue('L'.$j, $item['license']);
            }
            if (array_key_exists('updatedAt', $item)) {
                $activeSheet->setCellValue('M'.$j, $item['updatedAt']);
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
            if (array_key_exists('lsDocIdentifier', $association)) {
                $activeSheet->setCellValue('A'.$j, $association['lsDocIdentifier']);
            }
            if (array_key_exists('lsDocUri', $association)) {
                $activeSheet->setCellValue('B'.$j, $association['lsDocUri']);
            }
            if (array_key_exists('originNodeIdentifier', $association)) {
                $activeSheet->setCellValue('C'.$j, $association['originNodeIdentifier']);
            }
            if (array_key_exists('destinationNodeIdentifier', $association)) {
                $activeSheet->setCellValue('D'.$j, $association['destinationNodeIdentifier']);
            }
            if (array_key_exists('type', $association)) {
                $activeSheet->setCellValue('E'.$j, $association['type']);
            }
            if (array_key_exists('group', $association)) {
                $activeSheet->setCellValue('F'.$j, $association['group']);
            }
            if (array_key_exists('groupName', $association)) {
                $activeSheet->setCellValue('G'.$j, $association['groupName']);
            }
            if (array_key_exists('updatedAt', $association)) {
                $activeSheet->setCellValue('H'.$j, $association['updatedAt']);
            }
            ++$j;
        }
    }
}
