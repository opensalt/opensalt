<?php

namespace CftfBundle\Api\v1p0\DTO;

use JMS\Serializer\Annotation as Serializer;

class ImsxCodeMinor
{
    /**
     * @var ImsxCodeMinorField[]
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("imsx_codeMinorField")
     * @Serializer\Type("array<CftfBundle\Api\v1p0\DTO\ImsxCodeMinorField>")
     */
    public $codeMinorField;

    public function __construct($fields)
    {
        $this->codeMinorField = $fields;
    }
}
