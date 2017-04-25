<?php

namespace Cftf\UtilBundle\Command;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateLexileCommand extends ContainerAwareCommand
{
    /** @var LsItem[] */
    private $lsItems;

    /** @var LsDefItemType[] */
    private $itemTypes;

    /** @var LsDoc */
    private $lsDoc;

    /** @var EntityManager */
    private $em;

    protected function configure()
    {
        $this
            ->setName('util:import:lexile')
            ->setDescription('Generate Lexile Framework')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $this->lsItems = [];
        $this->itemTypes = [];

        $itemType = new LsDefItemType();
        $itemType->setCode('Reading Level');
        $itemType->setTitle('Reading Level');
        $itemType->setHierarchyCode('Reading Level');

        $this->em->persist($itemType);
        $this->itemTypes['Reading Level'] = $itemType;

        $itemType = new LsDefItemType();
        $itemType->setCode('Reading Level');
        $itemType->setTitle('Reading Level');
        $itemType->setHierarchyCode('Reading Level');

        $this->em->persist($itemType);
        $this->itemTypes['Reading Levels'] = $itemType;


        $this->lsDoc = new LsDoc();
        $this->lsDoc->setTitle('Lexile');
        $this->lsDoc->setCreator('Reading Levels');
        $this->em->persist($this->lsDoc);

        $lsItem = $this->createReadingLevel('BR', 'Reading Level', 0);

        for ($i = 0; $i <= 2000; ++$i) {
            $lsItem = $this->createReadingLevel($i.'L', 'Reading Level', $i+1);
        }

        $lsItem = $this->createReadingLevel('BR-0L', 'Reading Levels', 0);
        $lsItem->addParent($this->lsDoc);
        $this->createAssociation($this->lsItems['BR'], $lsItem, LsAssociation::CHILD_OF, 0);
        $this->createAssociation($this->lsItems['0L'], $lsItem, LsAssociation::CHILD_OF, 1);
        $this->createAssociation($this->lsItems['BR'], $lsItem, LsAssociation::PART_OF, 0);
        $this->createAssociation($this->lsItems['0L'], $lsItem, LsAssociation::PART_OF, 1);

        $lvl2Size = 10;
        for ($i = 1; $i <= 2000; $i += $lvl2Size) {
            $lsItem = $this->createReadingLevel($i.'L-'.($i+$lvl2Size-1).'L', 'Reading Levels', $i);
            for ($j = $i, $jMax = $i+$lvl2Size; $j < $jMax; ++$j) {
                $this->createAssociation($this->lsItems[$j.'L'], $lsItem, LsAssociation::CHILD_OF, $j+1);
                $this->createAssociation($this->lsItems[$j.'L'], $lsItem, LsAssociation::PART_OF, $j+1);
            }
        }

        $lvl3Size = 100;
        for ($i = 1; $i <= 2000; $i += $lvl3Size) {
            $lsItem = $this->createReadingLevel($i.'L-'.($i+$lvl3Size-1).'L', 'Reading Levels', $i);
            $lsItem->addParent($this->lsDoc);
            for ($j = $i, $jMax = $i+$lvl3Size; $j < $jMax; $j += $lvl2Size) {
                $this->createAssociation($this->lsItems[$j.'L-'.($j+$lvl2Size-1).'L'], $lsItem, LsAssociation::CHILD_OF, $j+1);
                $this->createAssociation($this->lsItems[$j.'L-'.($j+$lvl2Size-1).'L'], $lsItem, LsAssociation::PART_OF, $j+1);
            }
        }

        dump(array_keys($this->lsItems));

        $this->em->flush();

        $output->writeln('Command result.');
    }

    /**
     * @param $lvl
     * @param string $type
     *
     * @return LsItem
     */
    protected function createReadingLevel($lvl, $type = 'Reading Level', $rank = 0)
    {
        $lsItem = new LsItem();
        $lsItem->setLsDoc($this->lsDoc);
        $lsItem->setItemType($this->itemTypes[$type]);
        $lsItem->setFullStatement($lvl);
        $lsItem->setHumanCodingScheme($lvl);
        $lsItem->setRank($rank);
        $lsItem->setListEnumInSource($rank);
        $this->lsItems[$lvl] = $lsItem;
        $this->em->persist($lsItem);

        return $lsItem;
    }

    protected function createAssociation($origin, $dest, $type, $rank = 0)
    {
        $association = new LsAssociation();
        $association->setLsDoc($this->lsDoc);
        $association->setOrigin($origin);
        $association->setDestination($dest);
        $association->setType($type);
        $association->setSequenceNumber($rank);
        $this->em->persist($association);

        return $association;
    }
}
