<?php

namespace App\DTO\CaseJson;

use Symfony\Component\Serializer\Annotation\SerializedName;

class CFPackage
{
    /**
     * @var CFPackageDocument
     *
     * @SerializedName("CFDocument")
     */
    public $cfDocument;

    /**
     * @var CFPackageItem[]|array
     *
     * @SerializedName("CFItems")
     */
    public $cfItems = [];

    /**
     * @var CFPackageAssociation[]|array
     *
     * @SerializedName("CFAssociations")
     */
    public $cfAssociations = [];

    /**
     * @var CFDefinition
     *
     * @SerializedName("CFDefinitions")
     */
    public $cfDefinitions = [];

    /**
     * @var CFRubric[]|array
     *
     * @SerializedName("CFRubrics")
     */
    public $cfRubrics = [];
}
