<?php

namespace App\Entity\Asn;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * AsnDocument
 */
class AsnDocument
{
    /**
     * @var AsnDocumentMetadata
     */
    public $metadata;

    /**
     * @var AsnStandardDocument
     */
    public $standardDocument;

    /**
     * @var AsnStandard[]|ArrayCollection
     */
    public $standards;


    public function __construct()
    {
        $this->standards = new ArrayCollection();
    }

    /**
     * @param $arr
     *
     * @return AsnDocument
     */
    public static function fromArray($arr)
    {
        /** @var AsnDocument $doc */
        $doc = new static();

        foreach ($arr as $key => $val) {
            $rec = $doc->recordFromArray($key, $val);

            if ($rec instanceof AsnDocumentMetadata) {
                $doc->metadata = $rec;
            } elseif ($rec instanceof AsnStandardDocument) {
                $doc->standardDocument = $rec;
            } elseif ($rec instanceof AsnStandard) {
                $doc->standards->set($key, $rec);
            }
        }

        return $doc;
    }

    /**
     * @param string $key
     * @param array $val
     *
     * @return AsnDocumentMetadata|AsnStandardDocument|AsnStandard|null
     */
    public function recordFromArray($key, $val)
    {
        $rec = null;

        if (array_key_exists('http://purl.org/ASN/schema/core/exportVersion', $val)) {
            /** @var AsnDocumentMetadata $rec */
            $rec = AsnDocumentMetadata::fromArray($val);
        } elseif (array_key_exists('http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $val)) {
            switch ($val['http://www.w3.org/1999/02/22-rdf-syntax-ns#type'][0]['value']) {
                case 'http://purl.org/ASN/schema/core/StandardDocument':
                    /** @var AsnStandardDocument $rec */
                    $rec = AsnStandardDocument::fromArray($val);
                    if (null === $rec->getIdentifier()) {
                        $id = new AsnValue();
                        $id->value = $key;
                        $id->type = 'uri';
                        $rec->setIdentifier(new ArrayCollection([$id]));
                    }
                    break;

                case 'http://purl.org/ASN/schema/core/Statement':
                    /** @var AsnStandard $rec */
                    $rec = AsnStandard::fromArray($val);
                    if (null === $rec->getIdentifier()) {
                        $id = new AsnValue();
                        $id->value = $key;
                        $id->type = 'uri';
                        $rec->setIdentifier(new ArrayCollection([$id]));
                    }
                    break;
            }
        }

        return $rec;
    }

    /**
     * @param $json
     *
     * @return AsnDocument
     */
    public static function fromJson($json)
    {
        $arr = json_decode($json, true);

        return self::fromArray($arr);
    }

    /**
     * Set metadata
     *
     * @param AsnDocumentMetadata $metadata
     *
     * @return AsnDocument
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Get metadata
     *
     * @return AsnDocumentMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Set standardDocument
     *
     * @param AsnStandardDocument $standardDocument
     *
     * @return AsnDocument
     */
    public function setStandardDocument($standardDocument)
    {
        $this->standardDocument = $standardDocument;

        return $this;
    }

    /**
     * Get standardDocument
     *
     * @return AsnStandardDocument
     */
    public function getStandardDocument()
    {
        return $this->standardDocument;
    }

    /**
     * Set standards
     *
     * @param AsnStandard[]|ArrayCollection $standards
     *
     * @return AsnDocument
     */
    public function setStandards($standards)
    {
        $this->standards = $standards;

        return $this;
    }

    public function addStandard($standard)
    {
        $this->standards->add($standard);
    }

    /**
     * Get standards
     *
     * @return AsnStandard[]|ArrayCollection
     */
    public function getStandards()
    {
        return $this->standards;
    }
}
