<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class CFPackageAssociation
{
    public UuidInterface $identifier;
    public string $associationType;
    public ?int $sequenceNumber = null;
    public string $uri;
    public LinkGenURI $originNodeURI;
    public LinkGenURI $destinationNodeURI;

    #[SerializedName('CFAssociationGroupingURI')]
    public ?LinkURI $cfAssociationGroupingURI = null;

    public \DateTimeInterface $lastChangeDateTime;
}
