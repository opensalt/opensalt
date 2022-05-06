<?php

namespace App\DTO\CaseJson;

use Symfony\Component\Serializer\Annotation\SerializedName;

class CFPackage
{
    #[SerializedName('CFDocument')]
    public CFPackageDocument $cfDocument;

    /**
     * @var CFPackageItem[]|array|null
     */
    #[SerializedName('CFItems')]
    public ?array $cfItems = [];

    /**
     * @var CFPackageAssociation[]|array|null
     */
    #[SerializedName('CFAssociations')]
    public ?array $cfAssociations = [];

    #[SerializedName('CFDefinitions')]
    public ?CFDefinition $cfDefinitions = null;

    /**
     * @var CFRubric[]|array|null
     */
    #[SerializedName('CFRubrics')]
    public ?array $cfRubrics = [];
}
