<?php

namespace App\DTO\CaseJson;

use Symfony\Component\Serializer\Annotation\SerializedName;

class CFDefinition
{
    /**
     * @var CFConcept[]|array|null
     */
    #[SerializedName('CFConcepts')]
    public ?array $cfConcepts = [];

    /**
     * @var CFSubject[]|array|null
     */
    #[SerializedName('CFSubjects')]
    public ?array $cfSubjects = [];

    /**
     * @var CFLicense[]|array|null
     */
    #[SerializedName('CFLicenses')]
    public ?array $cfLicenses = [];

    /**
     * @var CFItemType[]|array|null
     */
    #[SerializedName('CFItemTypes')]
    public ?array $cfItemTypes = [];

    /**
     * @var CFAssociationGrouping[]|array|null
     */
    #[SerializedName('CFAssociationGroupings')]
    public ?array $cfAssociationGroupings = [];
}
