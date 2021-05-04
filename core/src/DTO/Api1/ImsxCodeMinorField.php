<?php

namespace App\DTO\Api1;

use JMS\Serializer\Annotation as Serializer;

class ImsxCodeMinorField
{
    public const CODE_MINOR_FULLSUCCESS = 'fullsuccess';
    public const CODE_MINOR_INVALID_SORT = 'invalid_sort_field';
    public const CODE_MINOR_INVALID_SELECTION = 'invalid_selection_field';
    public const CODE_MINOR_FORBIDDEN = 'forbidden';
    public const CODE_MINOR_UNAUTHORISED = 'unauthorisedrequest';
    public const CODE_MINOR_INTERNAL_SERVER_ERROR = 'internal_server_error';
    public const CODE_MINOR_UNKNOWN_OBJECT = 'unknownobject';
    public const CODE_MINOR_SERVER_BUSY = 'server_busy';
    public const CODE_MINOR_INVALID_UUID = 'invaliduuid';

    /**
     * @Serializer\Exclude()
     */
    public static array $codeMinorValues = [
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
     * @Serializer\SerializedName("imsx_codeMinorFieldName")
     */
    public string $name;

    /**
     * @Serializer\SerializedName("imsx_codeMinorFieldValue")
     */
    public string $value;

    public function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;

        if (!in_array($value, static::$codeMinorValues, true)) {
            throw new \InvalidArgumentException("Value {$value} is invalid.");
        }
    }
}
