<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class CFPackageItem
{
    /** @var UuidInterface */
    public $identifier;

    /** @var string */
    public $uri;

    /** @var string */
    public $fullStatement;

    /** @var string|null */
    public $alternativeLabel;

    /**
     * @var string|null
     *
     * @SerializedName("CFItemType")
     */
    public $cfItemType;

    /** @var string|null */
    public $humanCodingScheme;

    /** @var string|null */
    public $listEnumeration;

    /** @var string|null */
    public $abbreviatedStatement;

    /** @var string[]|array|null */
    public $conceptKeywords;

    /** @var LinkURI|null */
    public $conceptKeywordsURI;

    /** @var string|null */
    public $notes;

    /** @var string|null */
    public $language;

    /** @var string[]|array|string */
    public $educationLevel;

    /**
     * @var LinkURI|null
     *
     * @SerializedName("CFItemTypeURI")
     */
    public $cfItemTypeURI;

    /** @var LinkURI|null */
    public $licenseURI;

    /** @var \DateTimeImmutable|null */
    public $statusStartDate;

    /** @var \DateTimeImmutable|null */
    public $statusEndDate;

    /** @var \DateTimeImmutable */
    public $lastChangeDateTime;
}
