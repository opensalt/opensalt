<?php

namespace App\DTO\CaseJson;

use Symfony\Component\Serializer\Annotation\SerializedName;

class CFDefinition
{
    /**
     * @var CFConcept[]|array
     *
     * @SerializedName("CFConcepts")
     */
    public $cfConcepts = [];

    /**
     * @var CFSubject[]|array
     *
     * @SerializedName("CFSubjects")
     */
    public $cfSubjects = [];

    /**
     * @var CFLicense[]|array
     *
     * @SerializedName("CFLicenses")
     */
    public $cfLicenses = [];

    /**
     * @var CFItemType[]|array
     *
     * @SerializedName("CFItemTypes")
     */
    public $cfItemTypes = [];

    /**
     * @var CFAssociationGrouping[]|array
     *
     * @SerializedName("CFAssociationGroupings")
     */
    public $cfAssociationGroupings = [];
}
