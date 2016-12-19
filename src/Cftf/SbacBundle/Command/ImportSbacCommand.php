<?php

namespace Cftf\SbacBundle\Command;

use CftfBundle\Entity\LsDefSubject;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportSbacCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('import:sbac')
            ->setDescription('Import Learning Standards SpreadSheet from SBAC')
            ->addArgument('filename', InputArgument::REQUIRED, 'File to load')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $filename = $input->getArgument('filename');

        $fileType = \PHPExcel_IOFactory::identify($filename);
        $reader = \PHPExcel_IOFactory::createReader($fileType);
        $ss = $reader->load($filename);

        $pubSheet = $ss->getSheetByName('Publication');
        $publication = [
            'publisher' => $pubSheet->getCell('A2')->getValue(),
            'publication' => $pubSheet->getCell('B2')->getValue(),
            'subject' => $pubSheet->getCell('C2')->getValue(),
            'version' => $pubSheet->getCell('D2')->getValue(),
        ];

        $lsDoc = new LsDoc();
        $lsDoc->setTitle($publication['publication']);
        $lsDoc->setCreator($publication['publisher']);
        $lsDoc->setPublisher($publication['publisher']);
        $lsDoc->setVersion($publication['version']);
        if ($subject = $publication['subject']) {
            $subject = ucfirst(preg_replace('#.*/#', '', $subject));

            $s = $em->getRepository('CftfBundle:LsDefSubject')->findBy(['title' => $subject]);
            if (null === $s) {
                $uuid = Uuid::uuid5(Uuid::fromString('cacee394-85b7-11e6-9d43-005056a32dda'), $subject);
                $s = new LsDefSubject();
                $s->setIdentifier($uuid);
                $s->setUri('local:'.$uuid->toString());
                $s->setTitle($subject);
                $s->setHierarchyCode('1');

                $subjects[$subject] = $s;

                $em->persist($s);
            }

            $lsDoc->addSubject($s);
        }
        //$lsDoc->setSubject($publication['subject']);

        $em->persist($lsDoc);

        /** @var LsItem[] $lsItems */
        $lsItems = [];


        $catSheet = $ss->getSheetByName('Categories');
        $row = 1;
        $categories = [];
        while ($level = $catSheet->getCellByColumnAndRow(1, ++$row)->getValue()) {
            $categories[$level] = $catSheet->getCellByColumnAndRow(0, $row)->getValue();
        }

        $stdSheet = $ss->getSheetByName('Standards');
        $recs = [];
        $done = false;
        $row = 1;
        while (!$done) {
            ++$row;

            $level = (int) $stdSheet->getCellByColumnAndRow(0, $row)->getValue();
            if (empty($level)) {
                $done = true;
            } else {
                $key = $stdSheet->getCellByColumnAndRow(1, $row)->getValue();
                if (preg_match('/^(?P<parent>.*)\|/', $key, $matches)) {
                    $parent = $matches['parent'];
                } else {
                    $parent = NULL;
                }
                $rec = [
                    'level' => $level,
                    'category' => (array_key_exists($level, $categories)
                        ?$categories[$level]
                        :NULL
                    ),
                    'key' => $key,
                    'parent' => $parent,
                    'name' => $stdSheet->getCellByColumnAndRow(2, $row)->getValue(),
                    'description' => $stdSheet->getCellByColumnAndRow(3, $row)->getValue(),
                    'shortname' => $stdSheet->getCellByColumnAndRow(4, $row)->getValue(),
                    'grades' => [],
                ];

                $recs[$rec['key']] = $rec;
            }
        }

        $gradesSheet = $ss->getSheetByName('Benchmark Grades');
        $done = false;
        $row = 1;
        while (!$done) {
            ++$row;
            $key = $gradesSheet->getCellByColumnAndRow(0, $row)->getValue();

            if (!empty($key)) {
                if (array_key_exists($key, $recs)) {
                    $grade = trim($gradesSheet->getCellByColumnAndRow(1, $row)->getValue());
                    if ($grade === 'KG') {
                        $grade = 'KG';
                    } elseif (is_numeric($grade)) {
                        if ($grade < 10) {
                            $grade = '0'.$grade;
                        }
                    } else {
                        $grade = 'OT';
                    }
                    $recs[$key]['grades'][] = $grade;
                } else {
                    // TODO: Not found
                }
            } else {
                $done = true;
            }
        }

        foreach ($recs as $rec) {
            $lsItem = new LsItem();
            $lsItem->setLsDoc($lsDoc);
            $lsItem->setHumanCodingScheme($rec['name']);
            $lsItem->setType($rec['category']);
            $lsItem->setFullStatement($rec['description']);
            if (!empty($rec['shortname'])) {
                $lsItem->setAbbreviatedStatement($rec['shortname']);
            }
            $lsItem->setEducationalAlignment(implode(',', $rec['grades']));
            $lsItems[$rec['key']] = $lsItem;

            $em->persist($lsItem);
        }

        foreach ($lsItems as $key => $lsItem) {
            if ($recs[$key]['parent']) {
                $parent = $lsItems[$recs[$key]['parent']];
                $parent->addChild($lsItem);
            } else {
                $lsDoc->addTopLsItem($lsItem);
            }
        }

        $em->flush();


        $output->writeln('Command result.');
    }

}
