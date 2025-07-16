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
        foreach ($this->codeMinorField as $value) {
            if (!$value instanceof ImsxCodeMinorField) {
                throw new \InvalidArgumentException('Argument $codeMinorField must be an array of ImsxCodeMinorField');
            }
        }
    }
}
