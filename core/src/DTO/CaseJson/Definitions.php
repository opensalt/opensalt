<?php

namespace App\DTO\CaseJson;

use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDefConcept;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;

class Definitions
{
    /**
     * @var LsDefAssociationGrouping[]
     */
    public ?array $associationGroupings = [];

    /**
     * @var LsDefConcept[]
     */
    public ?array $concepts = [];

    /**
     * @var LsDefItemType[]
     */
    public ?array $itemTypes = [];

    /**
     * @var LsDefLicence[]
     */
    public ?array $licences = [];

    /**
     * @var LsDefSubject[]
     */
    public ?array $subjects = [];
}
