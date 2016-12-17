<?php

namespace Cftf\UtilBundle\Command;

use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportDol1Command extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('util:import:dol1')
            ->setDescription('Import Department of Labour CSV file')
            ->addArgument('filename', InputArgument::REQUIRED, 'Standards CSV File')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $filename = $input->getArgument('filename');

        $fd = fopen($filename, 'rb');

        // Get Title
        $rec = fgetcsv($fd, 0, ',');

        $lsDoc = new LsDoc();
        $lsDoc->setTitle($rec[0]);
        $lsDoc->setCreator('Department of Labor');

        $em->persist($lsDoc);

        $items = [];

        $i = 1;
        /** @var LsItem $lastItem */
        $lastItem = false;
        $currentCategory = '';
        $currentCategoryId = '';
        $currentCategorySubId = '';
        while (FALSE !== ($rec = fgetcsv($fd, 0, ","))) {
            // Skip empty lines
            if (empty($rec[0])) {
                continue;
            }

            $line = $rec[0];

            $lsItem = new LsItem();
            $lsItem->setLsDoc($lsDoc);

            if (preg_match('/^Tier (\d+)(.*)/', $line, $matches)) {
                $id = $matches[1];
                $statement = $line;
                $currentCategory = '';
                $currentCategoryId = '';
                $currentCategorySubId = '';
            } elseif (preg_match('/^[s\x7f-\xff]*(\d[\d\.]*)[\s\x7f-\xff]+(.*)/', $line, $matches)) {
                $id = $matches[1];
                $statement = $matches[2];
                if (1 == substr_count($id, '.')) {
                    // The categories are only between the 2nd and 3rd level, reset when back at level 2
                    $currentCategory = $id;
                    $currentCategoryId = '';
                    $currentCategorySubId = '';
                }
            } elseif (preg_match('/^o[\s\x7f-\xff]/', $line, $matches)) {
                // Continue last line, append this (prefix $line with \n)
                $lastItem->setFullStatement($lastItem->getFullStatement()."\n".$line);
                continue;
            } elseif (preg_match('/^(.*):[\s\x7f-\xff]*/', $line, $matches)) {
                // Make the parent of following (level 3) items
                $statement = $matches[1];
                if (empty($currentCategorySubId)) {
                    $currentCategorySubId = 'A';
                } else {
                    ++$currentCategorySubId;
                }
                $id = $currentCategoryId = $currentCategory.'.'.$currentCategorySubId;
            } else {
                // Unknown value
                throw new \Exception(sprintf("Unknown Value '%s' found.", $line));
            }

            $lsItem->setFullStatement($statement);
            $lsItem->setHumanCodingScheme($id);
            $lsItem->setRank($i++);


            $level = substr_count($id, '.') + 1;
            if (1 === $level) {
                $parentId = 0;
            } elseif (3 === $level && !empty($currentCategoryId) && $id !== $currentCategoryId) {
                $parentId = $currentCategoryId;
            } else {
                if (preg_match('/^(\d[\d\.]*)\.\w+$/', $id, $idMatches)) {
                    $parentId = $idMatches[1];
                } else {
                    $parentId = '';
                }
            }

            if (!empty($items[$parentId])) {
                $lsItem->addParent($items[$parentId]);
            } else {
                $lsItem->addParent($lsDoc);
            }

            $em->persist($lsItem);

            $items[$id] = $lsItem;
            $lastItem = $lsItem;
        }
        fclose($fd);

        $em->flush();

        $output->writeln('Done.');
    }

}
