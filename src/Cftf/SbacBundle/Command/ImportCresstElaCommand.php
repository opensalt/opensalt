<?php

namespace Cftf\SbacBundle\Command;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCresstElaCommand extends ContainerAwareCommand
{
    const CRESST_NS = 'f007c085-7a20-4d4a-b4db-e980603680b0';

    protected function configure()
    {
        $this
            ->setName('import:cresst-ela')
            ->setDescription('Import CRESST ELA Excel file')
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

        // Parse items
        $sheet = $ss->getSheetByName('Item');
        $items = [];
        $done = false;
        $row = 4;
        while (!$done) {
            ++$row;

            $item = [
                'doc' => $sheet->getCellByColumnAndRow(0, $row)->getValue(),
                'type' => $sheet->getCellByColumnAndRow(1, $row)->getValue(),
                'claimCode' => $sheet->getCellByColumnAndRow(2, $row)->getValue(),
                'claimName' => $sheet->getCellByColumnAndRow(3, $row)->getValue(),
                'domainCode' => $sheet->getCellByColumnAndRow(4, $row)->getValue(),
                'domainName' => $sheet->getCellByColumnAndRow(5, $row)->getValue(),
                'targetCode' => $sheet->getCellByColumnAndRow(6, $row)->getValue(),
                'targetName' => $sheet->getCellByColumnAndRow(7, $row)->getValue(),
                'ccssCode' => $sheet->getCellByColumnAndRow(8, $row)->getValue(),
                'fullStatement' => $sheet->getCellByColumnAndRow(9, $row)->getValue(),
                'educationLevel' => $sheet->getCellByColumnAndRow(10, $row)->getValue(),
                'legacyCodingScheme' => $sheet->getCellByColumnAndRow(11, $row)->getValue(),
                'coding' => [
                    'doc' => $sheet->getCellByColumnAndRow(12, $row)->getValue(),
                    'grade' => $sheet->getCellByColumnAndRow(13, $row)->getValue(),
                    'claim' => $sheet->getCellByColumnAndRow(14, $row)->getValue(),
                    'target' => $sheet->getCellByColumnAndRow(15, $row)->getValue(),
                    'ccss' => $sheet->getCellByColumnAndRow(16, $row)->getValue(),
                ],
                'codingComplete' => $sheet->getCellByColumnAndRow(17, $row)->getValue(),
                'associations' => [
                    'Is Child Of' => [],
                    'Is Related To' => [],
                    'Precedes' => [],
                ],
            ];

            if (empty($item['doc'])) {
                $done = true;
                continue;
            }

            $items[strtoupper($item['codingComplete'])] = $item;
        }


        // Parse associations
        $sheet = $ss->getSheetByName('Assoc');
        $associations = [];
        $done = false;
        $row = 4;
        while (!$done) {
            ++$row;

            $association = [
                'origin' => $sheet->getCellByColumnAndRow(0, $row)->getValue(),
                'type' => $sheet->getCellByColumnAndRow(1, $row)->getValue(),
                'destination' => $sheet->getCellByColumnAndRow(2, $row)->getValue(),
            ];

            if (empty($association['origin'])) {
                $done = true;
                continue;
            }

            $associations[] = $association;
            if (!empty($items[strtoupper($association['origin'])])) {
                $items[strtoupper($association['origin'])]['associations'][$association['type']][] = $association['destination'];
            } else {
                $output->writeln('<error>Missing origin</error>');
                dump($association);
            }
        }


        // Create structure

        $lsDoc = new LsDoc();
        $lsDoc->setCreator('CRESST');
        $lsDoc->setPublisher('PCG');
        $lsDoc->setTitle('SBAC ELA Targets');
        $em->persist($lsDoc);

        $lsItems = [];
        $itemTypes = [];
        $i = 0;
        foreach ($items as $key => $item) {
            $lsItemIdentifier = Uuid::uuid5(self::CRESST_NS, $key)->toString();
            $lsItem = new LsItem($lsItemIdentifier);
            $lsItem->setLsDoc($lsDoc);
            $lsItem->setExtraProperty('source', $item);
            $lsItem->setRank(++$i);

            if (!empty($item['fullStatement'])) {
                $lsItem->setFullStatement($item['fullStatement']);
            } elseif ('Grade' === $item['type'] && !empty($item['educationLevel'])) {
                $lsItem->setFullStatement($item['educationLevel']);
            } elseif ('Domain' === $item['type'] && !empty($item['domainName'])) {
                $lsItem->setFullStatement($item['domainName']);
            } elseif ('Target' === $item['type'] && !empty($item['targetName'])) {
                $lsItem->setFullStatement($item['targetName']);
            } else {
                $output->writeln('<error>Cannot find fullStatement</error>');
                dump($item);

                // Fail as we do not know what the statement should be
                return 1;
            }

            switch ($item['type']) {
                case 'Grade':
                    $lsItem->setAbbreviatedStatement($item['educationLevel']);
                    $lsDoc->addTopLsItem($lsItem);
                    break;

                case 'Claim':
                    $lsItem->setAbbreviatedStatement($item['claimName']);
                    break;

                case 'Domain':
                    $lsItem->setAbbreviatedStatement($item['domainName']);
                    break;

                case 'Target':
                    $lsItem->setAbbreviatedStatement($item['targetName']);
                    break;

                case 'Measured Skill':
                    // Ignore
                    break;

                default:
                    $output->writeln('<error>Unknown item type</error>');
                    dump($item);

                    // Fail as we do not know what the type is
                    return 1;
            }

            // Normalize education level
            $lvl = $item['educationLevel'];
            switch ($lvl) {
                case 'K':
                    $educationLevel = 'KG';
                    break;
                case 'Pre-K':
                    $educationLevel = 'PK';
                    break;
                case 'HS':
                    $educationLevel = '09,10,11,12';
                    break;
                default:
                    if (is_numeric($lvl)) {
                        if ($lvl < 10) {
                            $educationLevel = '0'.((int) $lvl);
                        } else {
                            $educationLevel = $lvl;
                        }
                    } else {
                        $educationLevel = 'OT';
                    }
            }
            $lsItem->setEducationalAlignment($educationLevel);

            $lsItem->setHumanCodingScheme($item['codingComplete']);

            if (!empty($itemTypes[$item['type']])) {
                $itemType = $itemTypes[$item['type']];
            } else {
                $itemType = $em->getRepository('CftfBundle:LsDefItemType')->findOneBy(['title' => $item['type']]);
                if (null === $itemType) {
                    $itemType = new LsDefItemType();
                    $itemType->setTitle($item['type']);
                    $itemType->setCode($item['type']);
                    $itemType->setHierarchyCode('1');
                    $em->persist($itemType);
                    $em->flush($itemType);
                }
                $itemTypes[$item['type']] = $itemType;
            }
            $lsItem->setItemType($itemType);

            $em->persist($lsItem);
            $lsItems[$key] = $lsItem;
        }

        // Add associations
        foreach ($items as $key => $item) {
            foreach ($item['associations'] as $associationType => $associations) {
                switch (strtoupper($associationType)) {
                    case 'IS CHILD OF':
                        $type = LsAssociation::CHILD_OF;
                        break;

                    case 'IS RELATED TO':
                        $type = LsAssociation::RELATED_TO;
                        break;
                    case 'PRECEDES':
                        $type = LsAssociation::PRECEDES;
                        break;

                    default:
                        $output->writeln('<error>Unknown association type</error>');
                        dump($associationType);

                        return 1;
                }

                foreach ($associations as $dest) {
                    $srcItem = $lsItems[strtoupper($key)];

                    if (empty($lsItems[strtoupper($dest)])) {
                        if (0 === strpos(strtoupper($dest), 'R-DOK')) {
                            // Skip DOK for now, TODO: Link to DOK
                            continue;
                        }

                        $output->writeln('<error>Unknown destination for association</error>');
                        dump($dest);

                        return 1;
                    }
                    $destItem = $lsItems[strtoupper($dest)];

                    $lsAssoc = new LsAssociation();
                    $lsAssoc->setLsDoc($lsDoc);
                    $lsAssoc->setOriginLsItem($srcItem);
                    $lsAssoc->setDestinationLsItem($destItem);
                    $lsAssoc->setType($type);
                    $em->persist($lsAssoc);
                }
            }
        }

        $em->flush();
    }
}
