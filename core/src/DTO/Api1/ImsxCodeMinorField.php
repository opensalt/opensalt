<?php

declare(strict_types=1);

namespace App\DTO\Api1;

class ImsxCodeMinorField
{
    final public const string CODE_MINOR_FULLSUCCESS = 'fullsuccess';
    final public const string CODE_MINOR_INVALID_SORT = 'invalid_sort_field';
    final public const string CODE_MINOR_INVALID_SELECTION = 'invalid_selection_field';
    final public const string CODE_MINOR_FORBIDDEN = 'forbidden';
    final public const string CODE_MINOR_UNAUTHORISED = 'unauthorisedrequest';
    final public const string CODE_MINOR_INTERNAL_SERVER_ERROR = 'internal_server_error';
    final public const string CODE_MINOR_UNKNOWN_OBJECT = 'unknownobject';
    final public const string CODE_MINOR_SERVER_BUSY = 'server_busy';
    final public const string CODE_MINOR_INVALID_UUID = 'invaliduuid';

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

    public function __construct(public string $name, public string $value)
    {
        if (!in_array($this->value, static::$codeMinorValues, true)) {
            throw new \InvalidArgumentException(sprintf('Value %s is invalid.', $this->value));
        }
    }
}
