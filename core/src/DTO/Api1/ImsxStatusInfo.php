<?php

declare(strict_types=1);

namespace App\DTO\Api1;

class ImsxStatusInfo
{
    final public const string CODE_MAJOR_SUCCESS = 'success';
    final public const string CODE_MAJOR_PROCESSING = 'processing';
    final public const string CODE_MAJOR_FAILURE = 'failure';
    final public const string CODE_MAJOR_UNSUPPORTED = 'unsupported';

    final public const string SEVERITY_STATUS = 'status';
    final public const string SEVERITY_WARNING = 'warning';
    final public const string SEVERITY_ERROR = 'error';

    public static array $codeMajorValues = [
        self::CODE_MAJOR_SUCCESS,
        self::CODE_MAJOR_PROCESSING,
        self::CODE_MAJOR_FAILURE,
        self::CODE_MAJOR_UNSUPPORTED,
    ];

    public static array $severityValues = [
        self::SEVERITY_STATUS,
        self::SEVERITY_WARNING,
        self::SEVERITY_ERROR,
    ];

    public function __construct(public string $codeMajor, public string $severity, public ?ImsxCodeMinor $codeMinor = null, public ?string $description = null)
    {
    }
}
