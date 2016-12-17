<?php

namespace Cftf\ImsBundle\Command;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportReadingLevelTxCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('import:reading-level-tx')
            ->setDescription('Import reading levels')
            ->addArgument('filename', InputArgument::REQUIRED, 'Reading Level CSV File')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $filename = $input->getArgument('filename');

        $fd = fopen($filename, 'r');

        $keys = fgetcsv($fd, 0, ',');

        $items = [];
        $lists = [];
        foreach ($keys as $key) {
            $lists[$key] = [];
        }
        while (FALSE !== ($rec = fgetcsv($fd, 0, ','))) {
            $item = array_combine($keys, $rec);
            $items[$rec[0]] = $item;
            foreach ($keys as $key) {
                if (!empty($item[$key])) {
                    $lists[$key][$item[$key]] = [];
                }
            }
        }
        fclose($fd);

        foreach ($items as $item) {
            foreach ($keys as $key) {
                foreach ($keys as $key2) {
                    if ($key === $key2) {
                        continue;
                    }
                    if (!empty($item[$key]) && !empty($item[$key2])) {
                        $lists[$key][$item[$key]][$key2][$item[$key2]] = 1;
                    }
                }
            }
        }

        $docs = [];
        $associations = [];

        foreach ($lists as $list => $values) {
            $output->writeln("<comment>Processing {$list}</comment>");
            $lsDoc = new LsDoc();
            $lsDoc->setTitle($list);
            $lsDoc->setCreator('HISD Reading');

            $docs[$list] = ['lsDoc' => $lsDoc, 'lsItems' => []];
            $associations[$list] = [];
            $em->persist($lsDoc);

            foreach ($values as $value => $relatesTo) {
                $output->writeln("<comment>  {$value}</comment>");
                $lsItem = new LsItem();
                $lsItem->setLsDoc($lsDoc);
                $lsItem->setType('Reading Level');
                $lsItem->setFullStatement($value);
                $lsItem->setHumanCodingScheme($value);
                $lsItem->addParent($lsDoc);

                $docs[$list]['lsItems'][$value] = $lsItem;
                $associations[$list][$value] = [];
                $em->persist($lsItem);

                foreach ($relatesTo as $otherList => $levelMap) {
                    // No relation -- if we get here then there is a relation

                    $levels = array_keys($levelMap);
                    switch (count($levels)) {
                        case 1:
                            // Equal -- if both directions only have 1
                            // Part Of -- if forward has 1 and backwards as > 1
                            if (1 === count($lists[$otherList][$levels[0]][$list])) {
                                $associations[$list][$value][] = [
                                    'doc' => $otherList,
                                    'type' => LsAssociation::EXACT_MATCH_OF,
                                    'key' => $levels[0],
                                ];
                            } else {
                                $associations[$list][$value][] = [
                                    'doc' => $otherList,
                                    'type' => LsAssociation::PART_OF,
                                    'key' => $levels[0],
                                ];
                            }
                            break;

                        default:
                            // Related -- if forward has > 1
                            //   Inverse Part Of -- if backwards = 1
                            foreach ($levels as $reverse) {
                                if (1 === count($lists[$otherList][$reverse][$list])) {
                                    $associations[$list][$value][] = [
                                        'doc' => $otherList,
                                        'type' => '-'.LsAssociation::PART_OF,
                                        'key' => $reverse,
                                    ];
                                } else {
                                    $associations[$list][$value][] = [
                                        'doc' => $otherList,
                                        'type' => LsAssociation::RELATED_TO,
                                        'key' => $reverse,
                                    ];
                                }
                            }
                            break;
                    }
                }
            }
        }

        foreach ($associations as $doc => $items) {
            $output->writeln("<comment>Mapping {$doc}</comment>");
            foreach ($items as $item => $relations) {
                foreach ($relations as $relation) {
                    $output->writeln("<comment>  Mapping {$item} to {$relation['key']}</comment>");
                    $association = new LsAssociation();
                    $association->setLsDoc($docs[$doc]['lsDoc']);

                    switch ($relation['type']) {
                        case LsAssociation::EXACT_MATCH_OF:
                        case LsAssociation::PART_OF:
                        case LsAssociation::RELATED_TO:
                            $association->setOrigin($docs[$doc]['lsItems'][$item]);
                            $association->setDestination($docs[$relation['doc']]['lsItems'][$relation['key']]);
                            $association->setType($relation['type']);
                            break;

                        case '-'.LsAssociation::PART_OF:
                            $association->setDestination($docs[$doc]['lsItems'][$item]);
                            $association->setOrigin($docs[$relation['doc']]['lsItems'][$relation['key']]);
                            $association->setType(LsAssociation::PART_OF);
                            break;
                    }

                    $em->persist($association);
                }
            }
        }

        $em->flush();

        $output->writeln('Done.');
    }

}
