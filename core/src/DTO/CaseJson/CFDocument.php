<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[OA\Schema()]
class CFDocument extends CFPackageDocument
{
    #[OA\Property(
        description: 'Reference to the package this document belongs to',
        ref: new Model(type: LinkURI::class)
    )]
    #[SerializedName('CFPackageURI')]
    public LinkURI $cfPackageURI;
}
