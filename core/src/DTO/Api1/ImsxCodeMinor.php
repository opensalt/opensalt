<?php

namespace App\DTO\Api1;

class ImsxCodeMinor
{
    /**
     * @var ImsxCodeMinorField[]
     */
    public array $codeMinorField = [];

    /**
     * @param array<ImsxCodeMinorField> $fields
     */
    public function __construct(array $fields)
    {
        $this->codeMinorField = $fields;
    }
}
