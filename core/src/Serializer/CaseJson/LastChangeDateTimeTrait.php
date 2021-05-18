<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\AbstractLsBase;

trait LastChangeDateTimeTrait
{
    protected function getLastChangeDateTime(AbstractLsBase $object): ?string
    {
        return $object->getChangedAt()->format('Y-m-d\TH:i:s+00:00');
    }
}
