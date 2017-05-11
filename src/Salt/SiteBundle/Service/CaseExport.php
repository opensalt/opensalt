<?php

namespace Salt\SiteBundle\Service;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Entity\LsAssociation;
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
     * @param array $smartLevel
     * @param PHPExcelObject $phpExcelObject
     */
    public function exportCaseFile($cfDoc, $items, $smartLevel, $phpExcelObject, $associations)
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
            $activeSheet
                ->setCellValue('A'.$j, $item['identifier'])
                ->setCellValue('B'.$j, $item['fullStatement'])
                ->setCellValue('C'.$j, $item['humanCodingScheme'])
                ->setCellValue('D'.$j, $smartLevel[$item['id']])
                ->setCellValue('E'.$j, $item['listEnumInSource'])
                ->setCellValue('F'.$j, $item['abbreviatedStatement'])
                ->setCellValue('G'.$j, $item['conceptKeywords'])
                ->setCellValue('H'.$j, $item['notes'])
                ->setCellValue('I'.$j, $item['language'])
                ->setCellValue('J'.$j, $item['educationalAlignment'])
                ->setCellValue('M'.$j, $item['updatedAt']);
            $j++;
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
            $activeSheet
                ->setCellValue('A'.$j, $association['lsDocIdentifier'])
                ->setCellValue('B'.$j, $association['lsDocUri'])
                ->setCellValue('C'.$j, $association['originNodeIdentifier'])
                ->setCellValue('D'.$j, $association['destinationNodeIdentifier'])
                ->setCellValue('E'.$j, $association['type'])
                ->setCellValue('F'.$j, $association['group'])
                ->setCellValue('G'.$j, $association['groupName'])
                ->setCellValue('H'.$j, $association['updatedAt']);
            $j++;
        }
    }
}
