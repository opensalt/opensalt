<?php

namespace App\DTO\Api1;

use JMS\Serializer\Annotation as Serializer;

class ImsxCodeMinor
{
    /**
     * @var ImsxCodeMinorField[]
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("imsx_codeMinorField")
     * @Serializer\Type("array<App\DTO\Api1\ImsxCodeMinorField>")
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
