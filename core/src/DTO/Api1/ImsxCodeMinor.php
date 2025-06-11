<?php

declare(strict_types=1);

namespace App\DTO\Api1;

class ImsxCodeMinor
{
    /**
     * @param array<ImsxCodeMinorField> $codeMinorField
     */
    public function __construct(public array $codeMinorField)
    {
    }
}
