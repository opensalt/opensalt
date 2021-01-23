<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class CFPackageDocument
{
    /** @var UuidInterface */
    public $identifier;

    /** @var string */
    public $uri;

    /** @var string */
    public $creator;

    /** @var string */
    public $title;

    /** @var \DateTimeInterface */
    public $lastChangeDateTime;

    /** @var string */
    public $officialSourceURL;

    /** @var string */
    public $publisher;

    /** @var string */
    public $description;

    /** @var string[]|string */
    public $subject;

    /** @var LinkURI[] */
    public $subjectURI;

    /** @var string */
    public $language;

    /** @var string */
    public $version;

    /** @var string */
    public $adoptionStatus;

    /** @var \DateTimeInterface|null */
    public $statusStartDate;

    /** @var \DateTimeInterface|null */
    public $statusEndDate;

    /** @var LinkURI|null */
    public $licenseURI;

    /** @var string */
    public $notes;
}
