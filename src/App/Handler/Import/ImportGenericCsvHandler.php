<?php

namespace App\Handler\Import;

use App\Command\Import\ImportGenericCsvCommand;
use App\Event\CommandEvent;
use App\Handler\BaseDoctrineHandler;
use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @DI\Service()
 */
class ImportGenericCsvHandler extends BaseDoctrineHandler
{
    /**
     * @DI\Observe(App\Command\Import\ImportGenericCsvCommand::class)
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ImportGenericCsvCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $filePath = $command->getFilePath();
        $creator = $command->getCreator() ?? 'System';
        $title = $command->getTitle() ?? 'Imported CSV';
        $organization = $command->getOrganization();

        $doc = $this->importCsv($filePath, $title, $creator);

        if ($organization) {
            $doc->setOrg($organization);
        }
    }

    private function importCsv(string $filename, string $title, string $creator): LsDoc
    {
        $fd = fopen($filename, 'rb');

        $doc = new LsDoc();
        $doc->setTitle($title);
        $doc->setCreator($creator);

        $this->em->persist($doc);

        $itemTypes = $this->em->getRepository(LsDefItemType::class)->getList();
        $items = [];

        // Ignore first row (assuming it is a header)
        fgetcsv($fd, 0, ',');

        $i = 1;
        while (FALSE !== ($rec = fgetcsv($fd, 0, ','))) {
            $item = new LsItem();
            $item->setLsDoc($doc);

            if (empty($itemTypes[$rec[0]])) {
                $itemType = new LsDefItemType();
                $itemType->setCode($rec[0]);
                $itemType->setTitle($rec[0]);
                $itemType->setHierarchyCode($rec[0]);

                $this->em->persist($itemType);
                $itemTypes[$rec[0]] = $itemType;
            }
            $item->setItemType($itemTypes[$rec[0]]);
            $item->setFullStatement($rec[1]);
            $item->setHumanCodingScheme($rec[2]);
            $item->setListEnumInSource($i);
            $item->setRank($i++);

            if (!empty($rec[3])) {
                if (!empty($items[$rec[3]])) {
                    $item->addParent($items[$rec[3]]);
                } else {
                    $item->addParent($doc);
                }
            } else {
                $item->addParent($doc);
            }

            if (!empty($rec[4])) {
                $item->setAbbreviatedStatement($rec[4]);
            }

            if (!empty($rec[5])) {
                $grades = [];
                if (is_numeric($rec[5])) {
                    if ($rec[5] < 10) {
                        $grades[] = '0'.((int) $rec[5]);
                    } else {
                        $grades[] = $rec[5];
                    }
                }

                if (0 < count($grades)) {
                    $item->setEducationalAlignment(implode(',', $grades));
                }
            }

            $this->em->persist($item);

            $items[$rec[2]] = $item;
        }
        fclose($fd);

        return $doc;
    }
}
