<?php

namespace Cftf\UtilBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCsvEvaluationDumpCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('util:export:csv-evaluation')
            ->setDescription('Export CSV file')
            ->addArgument('docId', InputArgument::REQUIRED, 'Document ID')
            ->addArgument('filename', InputArgument::REQUIRED, 'CSV File')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $filename = $input->getArgument('filename');

        $fd = fopen($filename, 'wb');

        $repo = $em->getRepository('CftfBundle:LsDoc');

        $lsDoc = $repo->find($input->getArgument('docId'));

        $items = $repo->findAllChildrenArray($lsDoc);
        $haveParents = $repo->findAllItemsWithParentsArray($lsDoc);
        $topChildren = $repo->findTopChildrenIds($lsDoc);

        $orphaned = $items;
        foreach ($haveParents as $child) {
            // Not an orphan
            $id = $child['id'];
            if (!empty($orphaned[$id])) {
                unset($orphaned[$id]);
            }
        }

        foreach ($topChildren as $itemId) {
            $this->writeItem($itemId, $items, 0, $fd);
        }

        fclose($fd);

        $output->writeln('Done.');
    }

    protected function writeItem($itemId, &$items, $level, $fd)
    {
        $item = $items[$itemId];
        // save row

        $line = [
            'Type' => (empty($item['itemType'])?'':$item['itemType']['title']),
            'Full Statement' => str_repeat(' ', $level).$item['fullStatement'],
            'Human Coding Scheme' => '',
            'Education Level' => "${item['educationalAlignment']}",
            'Legacy Coding Scheme' => $item['humanCodingScheme'],
        ];

        $line = array_values($line);
        fwrite($fd, \App\Util\CsvUtil::arrayToCsv($line)."\n");

        if (!empty($item['children'])) {
            $children = $item['children'];
            foreach ($children as $child) {
                $this->writeItem($child['id'], $items, $level+1, $fd);
            }
        }
    }
}
