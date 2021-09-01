<?php

namespace App\Serializer\CaseJson;

trait DateCallbackTrait
{
    protected function toDate(?\DateTimeInterface $dateTime): ?string
    {
        if (null === $dateTime) {
            return null;
        }

        return $dateTime->format('Y-m-d');
    }
}
