<?php

declare(strict_types=1);

namespace App\Serializer\CaseJson;

trait DateCallbackTrait
{
    protected function toDate(?\DateTimeInterface $dateTime): ?string
    {
        return $dateTime?->format('Y-m-d');
    }
}
