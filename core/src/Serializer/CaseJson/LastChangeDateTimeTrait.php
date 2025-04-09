<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\IdentifiableInterface;

trait LastChangeDateTimeTrait
{
    protected function getLastChangeDateTime(IdentifiableInterface $object): ?string
    {
        return $object->getChangedAt()->format('Y-m-d\TH:i:s+00:00');
    }
}
