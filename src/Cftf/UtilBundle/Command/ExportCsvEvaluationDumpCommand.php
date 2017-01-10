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

        $orphaned = $items;
        /* This list is now found in the $haveParents list
        foreach ($lsDoc->getTopLsItemIds() as $id) {
            // Not an orphan
            if (!empty($orphaned[$id])) {
                unset($orphaned[$id]);
            }
        }
        */
        foreach ($haveParents as $child) {
            // Not an orphan
            $id = $child['id'];
            if (!empty($orphaned[$id])) {
                unset($orphaned[$id]);
            }
        }

        foreach ($lsDoc->getTopLsItemIds() as $itemId) {
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
        //fputcsv($fd, array_values($line));
        $line = array_values($line);
        fwrite($fd, $this->arrayToCsv($line)."\n");

        if (!empty($item['children'])) {
            $children = $item['children'];
            foreach ($children as $child) {
                $this->writeItem($child['id'], $items, $level+1, $fd);
            }
        }
    }

    /**
     * Formats a line (passed as a fields  array) as CSV and returns the CSV as a string.
     * Adapted from http://us3.php.net/manual/en/function.fputcsv.php#87120
     *
     * @param array $fields
     * @param string $delimiter
     * @param string $enclosure
     * @param bool $encloseAll
     * @param bool $nullToMysqlNull
     *
     * @return string
     */
    protected function arrayToCsv(array &$fields, $delimiter = ',', $enclosure = '"', $encloseAll = true, $nullToMysqlNull = false)
    {
        $delimiterEsc = preg_quote($delimiter, '/');
        $enclosureEsc = preg_quote($enclosure, '/');

        $output = array();
        foreach ($fields as $field) {
            if ($field === null && $nullToMysqlNull) {
                $output[] = 'NULL';
                continue;
            }

            // Enclose fields containing $delimiter, $enclosure or whitespace
            if ($encloseAll || preg_match("/(?:${delimiterEsc}|${enclosureEsc}|\s)/", $field)) {
                $output[] = $enclosure.str_replace($enclosure, $enclosure.$enclosure, $field).$enclosure;
            } else {
                $output[] = $field;
            }
        }

        return implode($delimiter, $output);
    }
}
