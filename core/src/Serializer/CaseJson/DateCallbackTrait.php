<?php

namespace App\Serializer\CaseJson;

trait DateCallbackTrait
{
    protected function toDate(?\DateTimeInterface $dateTime): ?string
    {
        return $dateTime?->format('Y-m-d');
    }
}
