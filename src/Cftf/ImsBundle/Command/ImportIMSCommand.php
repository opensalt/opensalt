<?php

namespace Cftf\ImsBundle\Command;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportIMSCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('import:ims')
            ->setDescription('Import EdPlan IMS Learning Standards CSV file from EdPlan IMS')
            ->addArgument('filename', InputArgument::REQUIRED, 'EdPlan IMS Learning Standards File')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $filename = $input->getArgument('filename');

        $fd = fopen($filename, 'r');
        stream_filter_append($fd, 'convert.iconv.ISO-8859-1/UTF-8');

        $keys = fgetcsv($fd, 0, "\t");
        //var_dump($keys);

        $items = [];
        while (FALSE !== ($rec = fgetcsv($fd, 0, "\t"))) {
            $item = array_combine($keys, $rec);
            // TODO: We can check if the key already exists, if it does then there is a problem with the file (should be unique)
            $items[$item['LearningStandard']] = $item;
        }

        fclose($fd);

        $lsDoc = new LsDoc();
        $lsDoc->setTitle(basename($filename));
        $lsDoc->setCreator('EdPlan IMS');
        $em->persist($lsDoc);

        /** @var LsItem[] $level1 */
        $level1 = [];

        /** @var LsItem[] $level2 */
        $level2 = [];

        $level1Grades = [];
        $level2Grades = [];

        /** @var LsItem[] $lsItems */
        $lsItems = [];

        /** @var LsDefItemType[] $itemTypes */
        $itemTypes = [];

        foreach ($items as $item) {
            if (array_key_exists('Status', $item) && ($item['Status'] !== 'Active')) {
                continue;
            }

            $lsItem = new LsItem();
            $lsItem->setLsDoc($lsDoc);
            $lsItem->setHumanCodingScheme($item['LearningStandard']);
            $lsItem->setFullStatement($item['Description']);
            if (!empty($item['StandardType'])) {
                if (array_key_exists($item['StandardType'], $itemTypes)) {
                    $itemType = $itemTypes[$item['StandardType']];
                } else {
                    $itemType = $em->getRepository('CftfBundle:LsDefItemType')->findOneByCode($item['StandardType']);
                    if (!$itemType) {
                        $itemType = new LsDefItemType();
                        $itemType
                            ->setCode($item['StandardType'])
                            ->setHierarchyCode($item['StandardType'])
                            ->setTitle($item['StandardType']);
                        $em->persist($itemType);
                    }
                    $itemTypes[$item['StandardType']] = $itemType;
                }

                $lsItem->setItemType($itemType);
            }
            $grades = [];
            if ('HS' === $item['GradeCode']) {
                $grades = ['09', '10', '11', '12'];
            } else {
                switch ($item['GradeCode']) {
                    case '0':
                        $grades[] = 'KG';
                        break;
                    default:
                        if (is_numeric($item['GradeCode'])) {
                            if ($item['GradeCode'] < 10) {
                                $grades[] = '0' . ((int) $item['GradeCode']);
                            } else {
                                $grades[] = $item['GradeCode'];
                            }
                        } else {
                            $grades[] = 'OT';
                        }
                }
            }
            $lsItem->setEducationalAlignment(implode(',', $grades));
            if ($item['DisplayOrder'] !== '') {
                $lsItem->setListEnumInSource($item['DisplayOrder']);
                if (is_numeric($item['DisplayOrder'])) {
                    $lsItem->setRank((int) $item['DisplayOrder']);
                }
            }
            $lsItem->setChangedAt(new \DateTime());

            $lsItems[$item['LearningStandard']] = $lsItem;

            $em->persist($lsItem);
        }

        $useSubjectGrade = false;
        $useChapterSection = false;

        foreach ($items as $item) {
            if (array_key_exists('Status', $item) && ($item['Status'] !== 'Active')) {
                continue;
            }

            $lsItem = $lsItems[$item['LearningStandard']];
            if (!empty($item['ParentStandard']) && !empty($lsItems[$item['ParentStandard']])) {
                $parent = $lsItems[$item['ParentStandard']];
                $parent->addChild($lsItem);
            } elseif ($useSubjectGrade && !empty($item['GradeCode']) && ($item['GradeCode'] !== 'NULL')) {
                // Add Content Area and Grade levels
                $gradeCode = $item['GradeCode'];
                $contentArea = $item['Content Area'];
                if (!array_key_exists($contentArea.$gradeCode, $level2)) {
                    if (!array_key_exists($contentArea, $level1)) {
                        // Create Section
                        $topLevel = new LsItem();
                        $topLevel->setLsDoc($lsDoc);
                        $topLevel->setExtraProperty('cmsId', strtolower($topLevel->getIdentifier()));
                        //$topLevel->setHumanCodingScheme($contentArea);
                        $topLevel->setFullStatement(ucwords(strtolower($contentArea)));
                        switch ($contentArea) {
                            case 'LANGUAGE ARTS':
                                $topLevel->setRank(10);
                                break;

                            case 'MATHEMATICS':
                                $topLevel->setRank(20);
                                break;

                            case 'SCIENCE':
                                $topLevel->setRank(30);
                                break;

                            case 'SOCIAL STUDIES':
                                $topLevel->setRank(40);
                                break;

                            default:
                                break;
                        }
                        $topLevel->setChangedAt(new \DateTime());
                        $level1[$contentArea] = $topLevel;
                        $em->persist($topLevel);
                        $lsDoc->addTopLsItem($topLevel);
                    }

                    // Add Grade to Subject
                    $secondLevel = new LsItem();
                    $secondLevel->setLsDoc($lsDoc);
                    $secondLevel->setExtraProperty('cmsId', strtolower($secondLevel->getIdentifier()));
                    //$secondLevel->setHumanCodingScheme($gradeCode);
                    switch ($gradeCode) {
                        case 'HS':
                            $secondLevel->setRank(12);
                            $secondLevel->setFullStatement('High School');
                            break;

                        default:
                            $secondLevel->setRank($gradeCode);
                            $secondLevel->setFullStatement('Grade '.$gradeCode);
                            break;
                    }
                    $secondLevel->setChangedAt(new \DateTime());
                    $level2[$contentArea.$gradeCode] = $secondLevel;
                    $em->persist($secondLevel);
                    $level1[$contentArea]->addChild($secondLevel);
                }

                // Add item to Grade Level
                $level2[$contentArea.$gradeCode]->addChild($lsItem);

                $itemGrades = preg_split('/,/', $lsItem->getEducationalAlignment());
                foreach ($itemGrades as $grade) {
                    if (empty($level1Grades[$contentArea])) {
                        $level1Grades[$contentArea] = [];
                    }
                    $level1Grades[$contentArea][$grade] = 1;

                    if (empty($level2Grades[$contentArea.$gradeCode])) {
                        $level2Grades[$contentArea.$gradeCode] = [];
                    }
                    $level2Grades[$contentArea.$gradeCode][$grade] = 1;
                }
            } elseif ($useChapterSection && !empty($item['SubsectionCode']) && ($item['SubsectionCode'] !== 'NULL')) {
                // Add Chapter and Section levels
                $chapterCode = (int) $item['SubsectionCode'];
                if (!array_key_exists($item['SubsectionCode'], $level2)) {
                    if (!array_key_exists($chapterCode, $level1)) {
                        // Create Section
                        $topLevel = new LsItem();
                        $topLevel->setLsDoc($lsDoc);
                        $topLevel->setExtraProperty('cmsId', strtolower($topLevel->getIdentifier()));
                        $topLevel->setHumanCodingScheme($chapterCode);
                        $topLevel->setFullStatement($item['Section']);
                        $topLevel->setRank($chapterCode);
                        $topLevel->setChangedAt(new \DateTime());
                        $level1[$chapterCode] = $topLevel;
                        $em->persist($topLevel);
                        $lsDoc->addTopLsItem($topLevel);
                    }

                    // Add Subsection to Section
                    $secondLevel = new LsItem();
                    $secondLevel->setLsDoc($lsDoc);
                    $secondLevel->setExtraProperty('cmsId', strtolower($secondLevel->getIdentifier()));
                    $secondLevel->setHumanCodingScheme($item['SubsectionCode']);
                    $secondLevel->setFullStatement($item['SubSection']?:"{$item['SubsectionNomenclature']} {$item['SubsectionCode']}");
                    $secondLevel->setRank($item['SubsectionCode']*100);
                    $secondLevel->setChangedAt(new \DateTime());
                    $level2[$item['SubsectionCode']] = $secondLevel;
                    $em->persist($secondLevel);
                    $level1[$chapterCode]->addChild($secondLevel);
                }

                // Add item to Subsection
                $level2[$item['SubsectionCode']]->addChild($lsItem);

                $itemGrades = preg_split('/,/', $lsItem->getEducationalAlignment());
                foreach ($itemGrades as $grade) {
                    if (empty($level1Grades[$chapterCode])) {
                        $level1Grades[$chapterCode] = [];
                    }
                    $level1Grades[$chapterCode][$grade] = 1;

                    if (empty($level2Grades[$item['SubsectionCode']])) {
                        $level2Grades[$item['SubsectionCode']] = [];
                    }
                    $level2Grades[$item['SubsectionCode']][$grade] = 1;
                }
            } else {
                $lsDoc->addTopLsItem($lsItem);
            }

            if (!empty($item['SORID']) && ($item['SORID'] !== 'NULL')) {
                $lsItem->setExtraProperty('cmsId', strtolower($item['SORID']));
                $lsItem->setExtraProperty('abGuid', strtolower($item['SORID']));

                $lsAssoc = new LsAssociation();
                $lsAssoc->setLsDoc($lsDoc);
                $lsAssoc->setOriginLsItem($lsItem);
                $lsAssoc->setType(LsAssociation::EXACT_MATCH_OF);
                if (\Ramsey\Uuid\Uuid::isValid($item['SORID'])) {
                    $lsAssoc->setDestination('urn:uuid:'.$item['SORID']);
                } else {
                    $lsAssoc->setDestination($item['SORID']);
                }

                $em->persist($lsAssoc);
            } else {
                $lsItem->setExtraProperty('cmsId', strtolower($lsItem->getIdentifier()));
            }
        }

        foreach ($level1Grades as $key => $grades) {
            $level1[$key]->setEducationalAlignment(implode(',', array_keys($grades)));
        }
        foreach ($level2Grades as $key => $grades) {
            $level2[$key]->setEducationalAlignment(implode(',', array_keys($grades)));
        }

        $em->flush();

        $output->writeln('Done.');
    }

}

/*
array(33) {
  [0]=> string(16) "EdPlanClientCode" -- TX
  [1]=> string(12) "DataProvider" -- Edplan IMS
  [2]=> string(12) "StandardsSet" -- "TEKS 2009 ELA" / "TEKS Unknown" / "NEW MATH TEKS" / ...
  [3]=> string(16) "LearningStandard" -- 110.19.B.07.16
  [4]=> string(12) "Content Area" -- LANGUAGE ARTS
  [5]=> string(13) "SubjectDomain" -- Writing
  [6]=> string(9) "GradeCode" -- 7 -- HS
  [7]=> string(14) "ParentStandard" 110.19.B.07
  [8]=> string(7) "Summary" -- 7.16(A)
  [9]=> string(14) "SummaryContext"  -- 7.16(A)
  [10]=> string(11) "Description" -- Statement here...
  [11]=> string(10) "StrandCode" -- Writing
  [12]=> string(8) "Category" -- "RC3" / Reasoning Skills / STAAR Study Skills / NULL
  [13]=> string(20) "CategoryNomenclature" -- "Reporting Category" / NULL / "Across RCs"
  [14]=> string(12) "StandardType" -- "Readiness"
  [15]=> string(11) "LSTypeStyle" -- No values currently
  [16]=> string(26) "ClassificationNomenclature" -- "Strand" / Genre / NULL
  [17]=> string(14) "Classification" -- "Science Process" / ...
  [18]=> string(18) "ClassificationCode" -- "Scientific Process" / ...
  [19]=> string(12) "LSClassStyle" -- No values currently
  [20]=> string(19) "SectionNomenclature" -- "Chapter"
  [21]=> string(7) "Section" -- "Chapter 110. English Language Arts and Reading"
  [22]=> string(11) "SectionCode" -- 110
  [23]=> string(22) "SubsectionNomenclature" -- "Section"
  [24]=> string(10) "SubSection" -- "English Language Arts and Reading, Grade 7"
  [25]=> string(14) "SubsectionCode" -- "110.19"
  [26]=> string(12) "ReadingLevel" -- No values currently
  [27]=> string(15) "YearImplemented" -- No values currently
  [28]=> string(14) "LSNomenclature" -- No values currently
  [29]=> string(12) "LSOtherStyle" -- No values currently
  [30]=> string(12) "DisplayOrder" -- "110192070"
  [31]=> string(6) "Status" -- "Active"
  [32]=> string(5) "SORID" -- AB Guid (or empty or "NULL")
}

 */
