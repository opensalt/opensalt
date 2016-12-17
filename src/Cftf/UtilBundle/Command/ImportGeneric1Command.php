<?php

namespace Cftf\UtilBundle\Command;

use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportGeneric1Command extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('util:import:generic1')
            ->setDescription('Import Generic CSV file (Type, Statement, Coding, Parent)')
            ->addArgument('filename', InputArgument::REQUIRED, 'Standards CSV File')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $filename = $input->getArgument('filename');

        $fd = fopen($filename, 'r');

        $lsDoc = new LsDoc();
        $lsDoc->setTitle('Imported CSV');
        $lsDoc->setCreator('System');

        $em->persist($lsDoc);

        $itemTypes = $em->getRepository('CftfBundle:LsDefItemType')->getList();
        $items = [];

        // Ignore first row (assuming it is a header)
        fgetcsv($fd, 0, ',');

        $i = 1;
        while (FALSE !== ($rec = fgetcsv($fd, 0, ','))) {
            $lsItem = new LsItem();
            $lsItem->setLsDoc($lsDoc);

            if (empty($itemTypes[$rec[0]])) {
                $itemType = new LsDefItemType();
                $itemType->setCode($rec[0]);
                $itemType->setTitle($rec[0]);
                $itemType->setHierarchyCode($rec[0]);

                $em->persist($itemType);
                $itemTypes[$rec[0]] = $itemType;
            }
            $lsItem->setItemType($itemTypes[$rec[0]]);
            $lsItem->setFullStatement($rec[1]);
            $lsItem->setHumanCodingScheme($rec[2]);
            $lsItem->setRank($i++);

            if (!empty($rec[3])) {
                if (!empty($items[$rec[3]])) {
                    $lsItem->addParent($items[$rec[3]]);
                } else {
                }
            } else {
                $lsItem->addParent($lsDoc);
            }

            $em->persist($lsItem);

            $items[$rec[2]] = $lsItem;
        }
        fclose($fd);

        $em->flush();

        $output->writeln('Done.');
    }

}
