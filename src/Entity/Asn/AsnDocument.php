<?php

namespace App\Entity\Asn;

use Doctrine\Common\Collections\ArrayCollection;

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

    public static function fromArray($arr): self
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
     * @return AsnDocumentMetadata|AsnStandardDocument|AsnStandard|null
     */
    public function recordFromArray(string $key, array $val)
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

    public static function fromJson(string $json): self
    {
        $arr = json_decode($json, true);

        return self::fromArray($arr);
    }

    public function setMetadata(AsnDocumentMetadata $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getMetadata(): AsnDocumentMetadata
    {
        return $this->metadata;
    }

    public function setStandardDocument(AsnStandardDocument $standardDocument): self
    {
        $this->standardDocument = $standardDocument;

        return $this;
    }

    public function getStandardDocument(): AsnStandardDocument
    {
        return $this->standardDocument;
    }

    /**
     * @param AsnStandard[]|ArrayCollection $standards
     */
    public function setStandards($standards): self
    {
        $this->standards = $standards;

        return $this;
    }

    public function addStandard($standard): void
    {
        $this->standards->add($standard);
    }

    /**
     * @return AsnStandard[]|ArrayCollection
     */
    public function getStandards(): ArrayCollection
    {
        return $this->standards;
    }
}
