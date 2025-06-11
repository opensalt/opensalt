<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

use Symfony\Component\Serializer\Annotation\SerializedName;

class CFDocument extends CFPackageDocument
{
    #[SerializedName('CFPackageURI')]
    public LinkURI $cfPackageURI;
}
