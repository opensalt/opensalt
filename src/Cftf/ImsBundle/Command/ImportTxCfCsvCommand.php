<?php
/**
 * Copyright (c) 2017 Public Consulting Group
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cftf\ImsBundle\Command;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefAssociationGrouping;
use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportTxCfCsvCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('import:tx-cfcsv')
            ->setDescription('Import TX Learning Standards CF foramtted CSV files from EdPlan IMS')
            ->addArgument('dirname', InputArgument::REQUIRED, 'TX Learning Standards File Directory')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $error = false;

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $dirname = $input->getArgument('dirname');

        $itemsKeyedBy = 'Identifier';

        //$subjects = $this->fetchFile($dirname.'/CFSubject.csv', 'URI');
        $docs = $this->fetchFile($dirname.'/CFDocument.csv', 'PackageURI');
        $itemTypes = $this->fetchFile($dirname.'/CFItemType.csv', 'URI');
        $items = $this->fetchFile($dirname.'/CFItem.csv', $itemsKeyedBy);
        $associationGroups = $this->fetchFile($dirname.'/CFAssociationGrouping.csv', 'URI');
        $associations = $this->fetchFile($dirname.'/CFAssociation.csv', 'URI');

        /** @var LsDoc[] $lsDocs */
        $lsDocs = [];
        $lsDocsByIdentifier = [];
        foreach ($docs as $key => $rec) {
            if (preg_replace('/^Document: /', '', $rec['GlobalIdentifier']) > 500) {
                continue;
            }
            $lsDoc = new LsDoc();
            $lsDoc->setTitle($rec['Title']);
            //$lsDoc->setCreator($rec['Creator']);
            $lsDoc->setCreator('TEKS-STAAR Core Learning Standards');
            $lsDoc->setDescription($rec['Description']);
            $lsDoc->setLanguage('en');
            $lsDoc->setPublisher($rec['Publisher']);
            $lsDoc->setOfficialUri($rec['OfficialSourceURI']);
            $lsDoc->setNote($rec['Notes']);
            $lsDoc->setSubject($rec['Subject']);
            //$lsDoc->setSubjects($lsSubjects[$rec['SubjectURI']]);

            $em->persist($lsDoc);

            $lsDocs[$key] = $lsDoc;
            $lsDocsByIdentifier[$rec['GlobalIdentifier']] = $lsDoc;
        }

        /** @var LsDefItemType[] $lsItemTypes */
        $lsItemTypes = [];
        foreach ($itemTypes as $key => $rec) {
            $lsItemType = new LsDefItemType();
            $lsItemType->setExtraProperty('_source', json_encode($rec));
            $lsItemType->setTitle($rec['Title']);
            $lsItemType->setDescription($rec['Description']);
            $lsItemType->setHierarchyCode($rec['HierarchyCode']);
            $lsItemType->setCode($rec['Identifier']);

            $em->persist($lsItemType);

            $lsItemTypes[$key] = $lsItemType;
        }

        /** @var LsItem[] $lsItems */
        $lsItems = [];
        foreach ($items as $key => $rec) {
            $lsItem = new LsItem();
            $lsItem->setExtraProperty('_source', json_encode($rec));
            $lsItem->setLsDoc($lsDocs[$rec['PackageURI']]);
            $fullStatement = $rec['fullStatement'];
            if (preg_replace('/^Document: /', '', $rec['CFDocumentID']) < 40) {
                $fullStatement = preg_replace('/^[(][^)]*[)]\s+/', '', $fullStatement);
            }
            $lsItem->setFullStatement($rec['fullStatement']);
            $lsItem->setItemType($lsItemTypes[$rec['CFItemTypeURI']]);
            $lsItem->setHumanCodingScheme($rec['HumanCodingScheme']);
            $lsItem->setListEnumInSource($rec['ListEnumeration']);
            if ($rec['AbbreviatedStatement'] !== $rec['fullStatement'] && 0 !== strpos($rec['fullStatement'], substr($rec['AbbreviatedStatement'], 0, -3))) {
                $lsItem->setAbbreviatedStatement($rec['AbbreviatedStatement']);
            }
            if (!empty($rec['Notes'])) {
                $lsItem->setNotes($rec['Notes']);
            }
            $lsItem->setLanguage('en');
            if ('HS' === $rec['EducationLevel']) {
                $grades = ['09', '10', '11', '12'];
            } else {
                switch ($rec['EducationLevel']) {
                    case '0':
                        $grades[] = 'KG';
                        break;
                    default:
                        if (is_numeric($rec['EducationLevel'])) {
                            if ($rec['EducationLevel'] < 10) {
                                $grades[] = '0'.((int) $rec['EducationLevel']);
                            } else {
                                $grades[] = $rec['EducationLevel'];
                            }
                        } else {
                            $grades[] = 'OT';
                        }
                }
            }
            $lsItem->setEducationalAlignment(implode(',', $grades));

            $em->persist($lsItem);

            $lsItems[$key] = $lsItem;
        }

        /** @var LsDefAssociationGrouping[] $lsAssocGroups */
        $lsAssocGroups = [];
        foreach ($associationGroups as $key => $rec) {
            $lsAssocGroup = new LsDefAssociationGrouping();
            $lsAssocGroup->setExtraProperty('_source', json_encode($rec));
            $lsAssocGroup->setTitle($rec['Title']);
            $lsAssocGroup->setDescription($rec['Description']);

            $em->persist($lsAssocGroup);

            $lsAssocGroups[$key] = $lsAssocGroup;
        }

        foreach ($associations as $key => $rec) {
            $lsAssoc = new LsAssociation();
            $assocGroup = $lsAssocGroups[$rec['CFAssociationGroupingURI']];
            $lsAssoc->setGroup($assocGroup);
            $lsAssoc->setLsDoc($lsDocs[$rec['PackageURI']]);
            if (!empty($lsItems[$rec['OriginNode'.$itemsKeyedBy]])) {
                $lsAssoc->setOrigin($lsItems[$rec['OriginNode'.$itemsKeyedBy]]);
                $lsItems[$rec['OriginNode'.$itemsKeyedBy]]->addAssociation($lsAssoc);
            } elseif (!empty($lsDocs[$rec['OriginNode'.$itemsKeyedBy]])) {
                $lsAssoc->setOrigin($lsDocs[$rec['OriginNode'.$itemsKeyedBy]]);
                $lsDocs[$rec['OriginNode'.$itemsKeyedBy]]->addAssociation($lsAssoc);
            } elseif (!empty($lsDocsByIdentifier[$rec['OriginNode'.$itemsKeyedBy]])) {
                $lsAssoc->setOrigin($lsDocsByIdentifier[$rec['OriginNode'.$itemsKeyedBy]]);
                $lsDocsByIdentifier[$rec['OriginNode'.$itemsKeyedBy]]->addAssociation($lsAssoc);
            } else {
                $output->writeln("<error>Unknown Origin Identifier: {$rec['OriginNode'.$itemsKeyedBy]}</error>");

                // Exit with an error
                $error = 1;
            }
            switch ($rec['AssociationType']) {
                case 'is Child of':
                case 'isChildOf':
                    $lsAssoc->setType(LsAssociation::CHILD_OF);
                    break;

                case 'isExactMatchOf':
                    $lsAssoc->setType(LsAssociation::EXACT_MATCH_OF);
                    break;

                case 'isPartOf':
                    $lsAssoc->setType(LsAssociation::PART_OF);
                    break;

                case 'isRelatedTo':
                    $lsAssoc->setType(LsAssociation::RELATED_TO);
                    break;

                default:
                    $output->writeln("<error>Unknown Association Type: {$rec['AssociationType']}</error>");

                    // Exit with an error
                    $error = true;
                    break;
            }
            if (!empty($lsItems[$rec['DestinationNode'.$itemsKeyedBy]])) {
                $lsAssoc->setDestination($lsItems[$rec['DestinationNode'.$itemsKeyedBy]]);
                $lsItems[$rec['DestinationNode'.$itemsKeyedBy]]->addInverseAssociation($lsAssoc);
            } elseif (!empty($lsDocs[$rec['DestinationNode'.$itemsKeyedBy]])) {
                $lsAssoc->setDestination($lsDocs[$rec['DestinationNode'.$itemsKeyedBy]]);
                $lsDocs[$rec['DestinationNode'.$itemsKeyedBy]]->addInverseAssociation($lsAssoc);
            } elseif (!empty($lsDocsByIdentifier[$rec['DestinationNode'.$itemsKeyedBy]])) {
                $lsAssoc->setDestination($lsDocsByIdentifier[$rec['DestinationNode'.$itemsKeyedBy]]);
                $lsDocsByIdentifier[$rec['DestinationNode'.$itemsKeyedBy]]->addInverseAssociation($lsAssoc);
            } else {
                $output->writeln("<error>Unknown Destination Identifier: {$rec['DestinationNode'.$itemsKeyedBy]}</error>");

                // Exit with an error
                $error = true;
            }

            $em->persist($lsAssoc);
        }

        // Add items that do not have a parent as a child to the doc
        foreach ($lsItems as $key => $lsItem) {
            /** @var $lsItem LsItem */
            if ($lsItem->getLsItemParent()->isEmpty()) {
                $lsItem->getLsDoc()->addTopLsItem($lsItem);
            }
        }

        if ($error) {
            return 1;
        }

        $em->flush();

        $output->writeln('Done.');
    }

    /**
     * @param string $filename
     * @param string $idKey
     *
     * @return array
     */
    private function fetchFile($filename, $idKey)
    {
        $recs = [];

        $fd = fopen($filename, 'rb');
        stream_filter_append($fd, 'convert.iconv.ISO-8859-1/UTF-8');

        $keys = fgetcsv($fd, 0, "\t");
        while (false !== ($line = fgetcsv($fd, 0, "\t"))) {
            $rec = array_combine($keys, $line);
            // TODO: We can check if the key already exists, if it does then there is a problem with the file (should be unique)
            $recs[$rec[$idKey]] = $rec;
        }

        fclose($fd);

        return $recs;
    }
}
