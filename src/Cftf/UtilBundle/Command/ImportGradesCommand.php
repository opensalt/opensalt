<?php

namespace Cftf\UtilBundle\Command;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportGradesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('util:import-grades')
            ->setDescription('Import Grade Levels')
            ->addArgument('filename', InputArgument::REQUIRED, 'Grade Level CSV File')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $filename = $input->getArgument('filename');

        $fd = fopen($filename, 'r');

        $lsDoc = new LsDoc();
        $lsDoc->setTitle('Grade Levels');
        $lsDoc->setCreator('System');

        $em->persist($lsDoc);

        $i = 1;
        while (FALSE !== ($rec = fgetcsv($fd, 0, ","))) {
            $lsItem = new LsItem();
            $lsItem->setLsDoc($lsDoc);

            $lsItem->setFullStatement($rec[0]);
            $lsItem->setHumanCodingScheme($rec[1]);
            $lsItem->setType('Grade Level');
            $lsItem->setListEnumInSource($i);
            $lsItem->setRank($i);
            ++$i;

            $lsItem->addParent($lsDoc);

            $em->persist($lsItem);
        }
        fclose($fd);

        $em->flush();

        $output->writeln('Done.');
    }

}
