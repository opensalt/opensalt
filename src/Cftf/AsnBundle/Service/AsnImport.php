<?php

namespace Cftf\AsnBundle\Service;

use Cftf\AsnBundle\Entity\AsnDocument;
use Cftf\AsnBundle\Entity\AsnStandard;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDefSubject;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Ramsey\Uuid\Uuid;

/**
 * Class LocalUriExtension.
 *
 * @DI\Service("cftf_import.asn")
 */
class AsnImport
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var \GuzzleHttp\Client
     */
    private $jsonClient;

    /**
     * @param EntityManager $em
     *
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager"),
     * })
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->em;
    }

    /**
     * Parse an ASN document into a LsDoc/LsItem hierarchy
     *
     * @param string $asnDoc
     * @param string|null $creator
     */
    public function parseAsnDocument($asnDoc, $creator = null)
    {
        $em = $this->getEntityManager();

        $doc = AsnDocument::fromJson($asnDoc);

        $md = $doc->getMetadata();
        $sd = $doc->getStandardDocument();
        $lsDoc = new LsDoc();

        $lsDocIdentifier = Uuid::uuid5(Uuid::NAMESPACE_URL, $sd->getIdentifier()->first()->value)->toString();
        $lsDoc->setIdentifier($lsDocIdentifier);
        $lsDoc->setUri('local:'.$lsDocIdentifier);

        $map = [ // LsDoc from AsnStandardDocument
            //'identifier' => 'identifier',
            //'uri' => 'identifier',
            'title' => 'title',
            'note' => 'description',
            'officialUri' => 'source',
            //'subjectUri' => 'subject',
            'creator' => 'publisher',
        ];
        foreach ($map as $ldProp => $sdProp) {
            $val = $sd->{$sdProp};
            if (null !== $val) {
                $ldCall = 'set'.ucfirst($ldProp);
                $propVal = $val->first()->value;

                $lsDoc->{$ldCall}($propVal);
            }
        }

        if ($subjects = $sd->getSubject()) {
            foreach ($subjects as $sdSubject) {
                $subject = ucfirst(preg_replace('#.*/#', '', $sdSubject->getValue()));

                $s = $em->getRepository('CftfBundle:LsDefSubject')->findOneBy(['title' => $subject]);
                if (null === $s) {
                    $uuid = Uuid::uuid5('cacee394-85b7-11e6-9d43-005056a32dda', $subject)->toString();
                    $s = new LsDefSubject();
                    $s->setIdentifier($uuid);
                    $s->setUri('local:'.$uuid);
                    $s->setTitle($subject);
                    $s->setHierarchyCode('1');

                    $subjects[$subject] = $s;

                    $em->persist($s);
                }

                $lsDoc->addSubject($s);
            }
        }
//        if ($subject = $sd->getSubject()) {
//            $subjectResponse = $this->jsonClient->request(
//                'GET', $subject->first()->value . '.json', [
//                    'timeout' => 60,
//                    'headers' => [
//                        'Accept' => 'application/json',
//                    ],
//                    'http_errors' => false,
//                ]
//            );
//
//            if ($subjectResponse->getStatusCode() === 200) {
//                $subjectArray = json_decode($subjectResponse->getBody()->getContents());
//                if (array_key_exists($subject->first()->value, $subjectArray)) {
//                    $subjectArray = $subjectArray[$subject->first()->value];
//                    if (array_key_exists('http://www.w3.org/2004/02/skos/core#prefLabel', $subjectArray)) {
//
//                    }
//                }
//            }
//        }

        if (!$lsDoc->getCreator()) {
            $lsDoc->setCreator($creator ?: 'Imported from ASN');
        } elseif (null !== $creator) {
            $lsDoc->setCreator($creator.' - '.$lsDoc->getCreator());
        }

        $em->persist($lsDoc);

        $lsAssociation = new LsAssociation();
        $lsAssociation->setLsDoc($lsDoc);
        $lsAssociation->setOrigin($lsDoc);
        $lsAssociation->setType(LsAssociation::EXACT_MATCH_OF);
        $lsAssociation->setDestinationNodeIdentifier($sd->getIdentifier()->first()->value);

        $em->persist($lsAssociation);

        foreach ($sd->getHasChild() as $val) {
            /** @var AsnStandard $child */
            $child = $doc->getStandards()->get($val->getValue());
            if ($child) {
                $lsItem = $this->parseAsnStandard($doc, $lsDoc, $child);
                $lsDoc->addTopLsItem($lsItem);
            }
        }

        $em->flush();
    }

    /**
     * @param AsnDocument $doc
     * @param LsDoc $lsDoc
     * @param AsnStandard $asnStandard
     *
     * @return LsItem
     */
    public function parseAsnStandard(AsnDocument $doc, LsDoc $lsDoc, AsnStandard $asnStandard)
    {
        $em = $this->getEntityManager();
        $lsItem = new LsItem();
        $lsItem->setIdentifier(null);
        $lsItem->setLsDoc($lsDoc);

        $lsItemIdentifier = Uuid::uuid5(Uuid::NAMESPACE_URL, $asnStandard->getIdentifier()->first()->value)->toString();
        $lsItem->setIdentifier($lsItemIdentifier);
        $lsItem->setUri('local:'.$lsItemIdentifier);

        $em->persist($lsItem);

        $map = [ // LsItem from AsnStandard
            //'uri' => 'identifier',
            //'identifier' => 'identifier',
            'fullStatement' => 'description',
            'humanCodingScheme' => 'statementNotation',
            'listEnumInSource' => 'listID',
            'notes' => 'comment',
        ];
        foreach ($map as $liProp => $sProp) {
            $val = $asnStandard->{$sProp};
            if (null !== $val) {
                $liCall = 'set'.ucfirst($liProp);
                $propVal = $val->first()->value;

                $lsItem->{$liCall}($propVal);
            }
        }

        if ($asnStandard->statementLabel) {
            $label = $asnStandard->statementLabel->first()->value;

            $itemType = $em->getRepository('CftfBundle:LsDefItemType')
                ->findOneBy(['title' => $label])
            ;
            if (null === $itemType) {
                $itemType = new LsDefItemType();
                $itemType->setTitle($label);
                $itemType->setCode($label);
                $itemType->setHierarchyCode('1');
                $em->persist($itemType);
                $em->flush($itemType);
            }

            $lsItem->setItemType($itemType);
        }

        if ($asnStandard->language) {
            $lang = $asnStandard->language->first()->value;
            if (preg_match('/eng$/', $lang)) {
                $lsItem->setLanguage('en');
            }
        }

        if ($asnStandard->educationLevel) {
            $levels = [];
            foreach ($asnStandard->educationLevel as $level) {
                $lvl = preg_replace('#.*/#', '', $level->value);
                switch ($lvl) {
                    case 'K':
                        $levels[] = 'KG';
                        break;
                    case 'Pre-K':
                        $levels[] = 'PK';
                        break;
                    default:
                        if (is_numeric($lvl)) {
                            if ($lvl < 10) {
                                $levels[] = '0'.((int) $lvl);
                            } else {
                                $levels[] = $lvl;
                            }
                        } else {
                            $levels[] = 'OT';
                        }
                }
            }

            if (0 <= count($levels)) {
                $lsItem->setEducationalAlignment(implode(',', $levels));
            }
        }

        if ($matches = $asnStandard->getExactMatch()) {
            foreach ($matches as $match) {
                $assoc = new LsAssociation();
                $assoc->setLsDoc($lsDoc);
                $assoc->setType(LsAssociation::EXACT_MATCH_OF);
                $assoc->setOriginLsItem($lsItem);
                $assoc->setDestinationNodeUri($match->value);
                $assoc->setDestinationNodeIdentifier($match->value);

                $lsItem->addAssociation($assoc);

                $em->persist($assoc);
            }
        }

        $lsAssociation = new LsAssociation();
        $lsAssociation->setLsDoc($lsDoc);
        $lsAssociation->setOrigin($lsItem);
        $lsAssociation->setType(LsAssociation::EXACT_MATCH_OF);
        $lsAssociation->setDestinationNodeIdentifier($asnStandard->getIdentifier()->first()->value);

        $em->persist($lsAssociation);

        if ($children = $asnStandard->getHasChild()) {
            foreach ($children as $val) {
                /** @var AsnStandard $child */
                $child = $doc->getStandards()->get($val->getValue());
                $childLsItem = $this->parseAsnStandard($doc, $lsDoc, $child);
                $lsItem->addChild($childLsItem);
            }
        }

        return $lsItem;
    }
}
