<?php

namespace App\Serializer\CaseJson;

trait LastChangeDateTimeTrait
{
    protected function getLastChangeDateTime($object): ?string
    {
        return $object->getChangedAt()->format('Y-m-d\TH:i:s+00:00');
    }
}
