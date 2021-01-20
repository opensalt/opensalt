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
    public $associationGroupings = [];

    /**
     * @var LsDefConcept[]
     */
    public $concepts = [];

    /**
     * @var LsDefItemType[]
     */
    public $itemTypes = [];

    /**
     * @var LsDefLicence[]
     */
    public $licences = [];

    /**
     * @var LsDefSubject[]
     */
    public $subjects = [];
}
