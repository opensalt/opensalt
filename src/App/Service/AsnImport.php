<?php

namespace App\Service;

use App\Entity\Asn\AsnDocument;
use App\Entity\Asn\AsnStandard;
use App\Entity\Asn\AsnValue;
use CftfBundle\Entity\IdentifiableInterface;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDefSubject;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\ClientInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Ramsey\Uuid\Uuid;

/**
 * @DI\Service()
 */
class AsnImport
{
    /**
     * @var EntityManager
     */
    private $em;

    /** @var ClientInterface */
    private $jsonClient;

    /**
     * @param EntityManager $em
     * @param ClientInterface $jsonClient
     *
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *     "jsonClient" = @DI\Inject("csa_guzzle.client.json"),
     * })
     */
    public function __construct(EntityManagerInterface $em, ClientInterface $jsonClient)
    {
        $this->em = $em;
        $this->jsonClient = $jsonClient;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->em;
    }

    /**
     * Parse an ASN document into a LsDoc/LsItem hierarchy
     *
     * @param string $asnDoc
     * @param string|null $creator
     *
     * @return LsDoc
     */
    public function parseAsnDocument(string $asnDoc, ?string $creator = null): LsDoc
    {
        $em = $this->getEntityManager();

        $doc = AsnDocument::fromJson($asnDoc);

        // Ignoring doc metadata that might be provided
        $sd = $doc->getStandardDocument();
        $lsDoc = new LsDoc();

        $map = [
            // LsDoc field from AsnStandardDocument field
            'title' => 'title',
            'note' => 'description',
            'officialUri' => 'source',
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
                // Note: We could also get more information about the subject at the URL provided
                $subject = ucfirst(preg_replace('#.*/#', '', $sdSubject->getValue()));

                $s = $em->getRepository(LsDefSubject::class)->findOneBy(['title' => $subject]);
                if (null === $s) {
                    $s = new LsDefSubject();
                    $s->setTitle($subject);
                    $s->setHierarchyCode('1');

                    $em->persist($s);
                }

                $lsDoc->addSubject($s);
            }
        }

        if (!$lsDoc->getCreator()) {
            $lsDoc->setCreator($creator ?: 'Imported from ASN');
        } elseif (null !== $creator) {
            $lsDoc->setCreator($creator.' - '.$lsDoc->getCreator());
        }

        $em->persist($lsDoc);

        $this->addExactMatch($lsDoc, $lsDoc, $sd->getIdentifier()->first());

        $seq = 0;
        foreach ($sd->getHasChild() as $val) {
            /** @var AsnStandard $child */
            $child = $doc->getStandards()->get($val->getValue());
            if ($child) {
                $lsItem = $this->parseAsnStandard($doc, $lsDoc, $child);
                $lsDoc->createChildItem($lsItem, null, ++$seq);
            }
        }

        return $lsDoc;
    }

    /**
     * @param AsnDocument $doc
     * @param LsDoc $lsDoc
     * @param AsnStandard $asnStandard
     *
     * @return LsItem
     */
    public function parseAsnStandard(AsnDocument $doc, LsDoc $lsDoc, AsnStandard $asnStandard): LsItem
    {
        $em = $this->getEntityManager();
        $lsItem = $lsDoc->createItem();

        $em->persist($lsItem);

        $map = [
            // LsItem field from AsnStandard field
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

            $itemType = $this->findItemType($label);

            $lsItem->setItemType($itemType);
        }

        if ($asnStandard->language) {
            $lang = $asnStandard->language->first()->value;
            if (preg_match('/eng$/', $lang)) {
                $lsItem->setLanguage('en');
            }
        }

        if ($asnStandard->educationLevel) {
            $levels = $this->getLevels($asnStandard->educationLevel);

            if (0 < count($levels)) {
                $levels = array_unique($levels);
                $lsItem->setEducationalAlignment(implode(',', $levels));
            }
        }

        if ($matches = $asnStandard->getExactMatch()) {
            $this->addExactMatches($lsDoc, $lsItem, $matches);
        }

        $this->addExactMatch($lsDoc, $lsItem, $asnStandard->getIdentifier()->first());

        $seq = 0;
        if ($children = $asnStandard->getHasChild()) {
            foreach ($children as $val) {
                /** @var AsnStandard $child */
                $child = $doc->getStandards()->get($val->getValue());
                $childLsItem = $this->parseAsnStandard($doc, $lsDoc, $child);
                $lsItem->addChild($childLsItem, null, ++$seq);
            }
        }

        return $lsItem;
    }

    /**
     * @param string $asnLocator
     * @param string|null $creator
     *
     * @return LsDoc
     */
    public function generateFrameworkFromAsn(string $asnLocator, ?string $creator = null): LsDoc
    {
        $asnDoc = $this->fetchAsnDocument($asnLocator);

        return $this->parseAsnDocument($asnDoc, $creator);
    }

    /**
     * @param string $asnLocator
     *
     * @return string
     *
     * @throws \Exception
     */
    public function fetchAsnDocument(string $asnLocator): string
    {
        $asnId = '';
        $asnHost = '';

        if (preg_match('/(D[\dA-F]+)/', $asnLocator, $matches)) {
            $asnId = $matches[1];
        }

        if (preg_match('!^(https?://[^/]+/resources/)!', $asnLocator, $matches)) {
            $asnHost = $matches[1];
        }

        if (!empty($asnHost)) {
            $urlPrefixes = [$asnHost];
        } else {
            $urlPrefixes = [
                'http://asn.jesandco.org/resources/',
                'http://asn.desire2learn.com/resources/',
            ];
        }

        foreach ($urlPrefixes as $urlPrefix) {
            try {
                $asnDoc = $this->requestAsnDocument($urlPrefix.$asnId.'_full.json');
                break;
            } catch (\Exception $e) {
                // If on the second ASN URL then the first will not be found
            }
        }

        if (empty($asnDoc)) {
            throw new \Exception('Error getting document from ASN.');
        }

        return $asnDoc;
    }

    /**
     * @param string $url
     *
     * @return string
     *
     * @throws \Exception
     */
    public function requestAsnDocument(string $url): string
    {
        $jsonClient = $this->jsonClient;

        $asnResponse = $jsonClient->request(
            'GET',
            $url,
            [
                'timeout' => 60,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'http_errors' => false,
            ]
        );

        if ($asnResponse->getStatusCode() !== 200) {
            throw new \Exception('Error getting document from ASN.');
        }

        return $asnResponse->getBody()->getContents();
    }

    /**
     * @param array|Collection $levelList
     *
     * @return array
     */
    protected function getLevels($levelList): array
    {
        $levels = [];

        foreach ($levelList as $level) {
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

        return $levels;
    }

    /**
     * @param string $label
     *
     * @return LsDefItemType
     */
    protected function addItemType(string $label): LsDefItemType
    {
        $itemType = new LsDefItemType();
        $itemType->setTitle($label);
        $itemType->setCode($label);
        $itemType->setHierarchyCode('1');
        $this->em->persist($itemType);

        return $itemType;
    }

    /**
     * @param LsDoc $lsDoc
     * @param IdentifiableInterface $origin
     * @param AsnValue[] $matches
     */
    protected function addExactMatches(LsDoc $lsDoc, IdentifiableInterface $origin, iterable $matches): void
    {
        foreach ($matches as $match) {
            $this->addExactMatch($lsDoc, $origin, $match);
        }
    }

    /**
     * @param LsDoc $lsDoc
     * @param IdentifiableInterface $origin
     * @param ASNValue $match
     *
     * @return LsAssociation
     */
    protected function addExactMatch(LsDoc $lsDoc, IdentifiableInterface $origin, AsnValue $match): LsAssociation
    {
        $assoc = $lsDoc->createAssociation();
        $assoc->setType(LsAssociation::EXACT_MATCH_OF);
        $assoc->setOrigin($origin);
        if ($origin instanceof LsItem) {
            $origin->addAssociation($assoc);
        }

        $value = $match->value;
        if (Uuid::isValid($value)) {
            $uriType = ';type=uuid';
            $identifier = $value;
        } elseif (false !== filter_var($value, FILTER_VALIDATE_URL)) {
            $uriType = ';type=url';
            $identifier = Uuid::uuid5(Uuid::NAMESPACE_URL, $value)->toString();
        } elseif (false !== preg_match('/^urn:guid:/', $value)) {
            // ASN uses "urn:guid:<uuid>" which is not really valid
            // Turn the value into a UUID for the identifier and mark as a guid
            $value = preg_replace('/^urn:guid:/', '', $value);
            $uriType = ';type=guid';
            $identifier = Uuid::fromString($value)->toString();
        } else {
            $uriType = '';
            $nsId = 'cd9d92fe-20fd-552f-9ee2-6df3f98a62de'; // Uuid::uuid5(NIL, 'data:text/x-ref;src=ASN')
            $identifier = Uuid::uuid5($nsId, $value)->toString();
        }
        $assoc->setDestination(
            "data:text/x-ref;src=ASN{$uriType},".rawurlencode($value),
            $identifier
        );

        $this->em->persist($assoc);

        return $assoc;
    }

    protected function findItemType(string $label): LsDefItemType
    {
        static $itemTypes = [];

        if (in_array($label, $itemTypes)) {
            return $itemTypes[$label];
        }

        $itemType = $this->getEntityManager()
            ->getRepository(LsDefItemType::class)
            ->findOneBy(['title' => $label]);

        if (null === $itemType) {
            $itemType = $this->addItemType($label);
            $itemTypes[$label] = $itemType;
        }

        return $itemType;
    }
}
