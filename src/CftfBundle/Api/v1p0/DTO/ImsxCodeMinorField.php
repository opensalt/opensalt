<?php

namespace CftfBundle\Api\v1p0\DTO;

use JMS\Serializer\Annotation as Serializer;

class ImsxCodeMinorField
{
    const CODE_MINOR_FULLSUCCESS = 'fullsuccess';
    const CODE_MINOR_INVALID_SORT = 'invalid_sort_field';
    const CODE_MINOR_INVALID_SELECTION = 'invalid_selection_field';
    const CODE_MINOR_FORBIDDEN = 'forbidden';
    const CODE_MINOR_UNAUTHORISED = 'unauthorisedrequest';
    const CODE_MINOR_INTERNAL_SERVER_ERROR = 'internal_server_error';
    const CODE_MINOR_UNKNOWN_OBJECT = 'unknownobject';
    const CODE_MINOR_SERVER_BUSY = 'server_busy';
    const CODE_MINOR_INVALID_UUID = 'invaliduuid';

    /**
     * @var array
     *
     * @Serializer\Exclude()
     */
    public static $codeMinorValues = [
        self::CODE_MINOR_FULLSUCCESS,
        self::CODE_MINOR_INVALID_SORT,
        self::CODE_MINOR_INVALID_SELECTION,
        self::CODE_MINOR_FORBIDDEN,
        self::CODE_MINOR_UNAUTHORISED,
        self::CODE_MINOR_INTERNAL_SERVER_ERROR,
        self::CODE_MINOR_UNKNOWN_OBJECT,
        self::CODE_MINOR_SERVER_BUSY,
        self::CODE_MINOR_INVALID_UUID,
    ];

    /**
     * @var string
     *
     * @Serializer\SerializedName("imsx_codeMinorFieldName")
     */
    public $name;

    /**
     * @var string
     *
     * @Serializer\SerializedName("imsx_codeMinorFieldValue")
     */
    public $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;

        if (!in_array($value, static::$codeMinorValues)) {
            throw new \InvalidArgumentException("Value {$value} is invalid.");
        }
    }
}
