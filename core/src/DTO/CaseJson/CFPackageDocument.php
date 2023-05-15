<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class CFPackageDocument
{
    public UuidInterface $identifier;
    public string $uri;
    public string $creator;
    public string $title;
    public \DateTimeInterface $lastChangeDateTime;
    public ?string $officialSourceURL = null;
    public ?string $publisher = null;
    public ?string $description = null;

    /** @var string[]|string */
    public string|array $subject;

    /** @var LinkURI[] */
    public ?array $subjectURI = [];

    public ?string $language = null;
    public ?string $version = null;
    public ?string $adoptionStatus = null;
    public ?\DateTimeInterface $statusStartDate = null;
    public ?\DateTimeInterface $statusEndDate = null;
    public ?LinkURI $licenseURI = null;
    public ?string $notes = null;
}
