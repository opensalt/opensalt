<?php
/**
 * Copyright (c) 2017 Public Consulting Group
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cftf\SbacBundle\Command;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportDlCcssCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('import:dl-ccss')
            ->setDescription('Import Digital Library CCSS export')
            ->addArgument('filename', InputArgument::REQUIRED, 'Standards CSV File')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $filename = $input->getArgument('filename');

        $fd = fopen($filename, 'rb');

        $lsDoc = new LsDoc();
        $lsDoc->setTitle('CCSS Imported from Digital Library');
        $lsDoc->setCreator('SBAC');

        $em->persist($lsDoc);

        /** @var LsItem[] $items */
        $items = [];

        // Get headers from first row (we are assuming it is a header)
        //"Name","Term description","Alignment Key",
        //"Taxonomy term UUID","Alignment Publication","Alignment ShortName","Alignment Grade","Weight","Parent UUID"
        $headers = fgetcsv($fd, 0, ',');

        while (false !== ($rec = fgetcsv($fd, 0, ','))) {
            $item = array_combine($headers, $rec);

            $lsItem = new LsItem($item['Taxonomy term UUID']);
            $lsItem->setLsDoc($lsDoc);
            $lsItem->setExtraProperty('_source', $item);

            $lsItem->setFullStatement($item['Term description']);
            if (!empty($item['Alignment ShortName']) && false === strpos($item['Alignment ShortName'], ' ')) {
                // Skip if there is a space, as some have part of a sentence instead
                $lsItem->setHumanCodingScheme($item['Alignment ShortName']);
            } else {
                $lsItem->setHumanCodingScheme($item['Name']);
            }
            if ('18379cc2-509c-4390-819b-d93169d0c0a9' === $item['Taxonomy term UUID']) {
                // ELA Root
                $lsItem->setRank(-2);
            } elseif ('7f84d3d1-2d6f-4f0d-9f25-bba0022746c7' === $item['Taxonomy term UUID']) {
                // Math Root
                $lsItem->setRank(-1);
            } else {
                $lsItem->setRank((int) $item['Weight']);
            }
            $lsItem->setExtraProperty('legacyCoding', $item['Alignment Key']);

            $lvl = $item['Alignment Grade'];
            switch ($lvl) {
                case 'Pre-K':
                    $educationLevel = 'PK';
                    break;
                case 'K':
                case 'KG':
                    $educationLevel = 'KG';
                    break;
                case '9-10':
                    $educationLevel = '09,10';
                    break;
                case '11-12':
                    $educationLevel = '11,12';
                    break;
                case 'HS':
                    $educationLevel = '09,10,11,12';
                    break;
                default:
                    if (empty($lvl)) {
                        // No educational level
                        $educationLevel = '';
                    } elseif (is_numeric($lvl)) {
                        if ($lvl < 10) {
                            $educationLevel = '0'.((int) $lvl);
                        } else {
                            $educationLevel = $lvl;
                        }
                    } else {
                        $educationLevel = 'OT';
                    }
            }
            if (!empty($educationLevel)) {
                $lsItem->setEducationalAlignment($educationLevel);
            }

            $em->persist($lsItem);

            $items[$item['Taxonomy term UUID']] = $lsItem;
        }
        fclose($fd);

        // Add parents
        foreach ($items as $lsItem) {
            $source = $lsItem->getExtraProperty('_source');
            $parentUuid = $source['Parent UUID'];

            if (!empty($parentUuid)) {
                if (!empty($items[$parentUuid])) {
                    $lsItem->addParent($items[$parentUuid]);
                } else {
                    $lsItem->addParent($lsDoc);
                }
            } else {
                $lsItem->addParent($lsDoc);
            }
        }

        $em->flush();

        $output->writeln('Done.');
    }

}
