<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class CFPackageAssociation
{
    /** @var UuidInterface */
    public $identifier;

    /** @var string */
    public $associationType;

    /** @var int */
    public $sequenceNumber;

    /** @var string */
    public $uri;

    /** @var LinkGenURI */
    public $originNodeURI;

    /** @var LinkGenURI */
    public $destinationNodeURI;

    /**
     * @var LinkURI|null
     *
     * @SerializedName("CFAssociationGroupingURI")
     */
    public $cfAssociationGroupingURI;

    /** @var \DateTimeInterface */
    public $lastChangeDateTime;
}
