<?php

namespace App\DTO\CaseJson;

use Symfony\Component\Serializer\Annotation\SerializedName;

class CFDocument extends CFPackageDocument
{
    #[SerializedName('CFPackageURI')]
    public LinkURI $cfPackageURI;
}
