<?php

namespace App\DTO\Api1;

class ImsxCodeMinorField
{
    final public const CODE_MINOR_FULLSUCCESS = 'fullsuccess';
    final public const CODE_MINOR_INVALID_SORT = 'invalid_sort_field';
    final public const CODE_MINOR_INVALID_SELECTION = 'invalid_selection_field';
    final public const CODE_MINOR_FORBIDDEN = 'forbidden';
    final public const CODE_MINOR_UNAUTHORISED = 'unauthorisedrequest';
    final public const CODE_MINOR_INTERNAL_SERVER_ERROR = 'internal_server_error';
    final public const CODE_MINOR_UNKNOWN_OBJECT = 'unknownobject';
    final public const CODE_MINOR_SERVER_BUSY = 'server_busy';
    final public const CODE_MINOR_INVALID_UUID = 'invaliduuid';

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

    public string $name;
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
