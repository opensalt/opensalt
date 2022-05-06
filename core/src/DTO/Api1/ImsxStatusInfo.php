<?php

namespace App\DTO\Api1;

class ImsxStatusInfo
{
    final public const CODE_MAJOR_SUCCESS = 'success';
    final public const CODE_MAJOR_PROCESSING = 'processing';
    final public const CODE_MAJOR_FAILURE = 'failure';
    final public const CODE_MAJOR_UNSUPPORTED = 'unsupported';

    final public const SEVERITY_STATUS = 'status';
    final public const SEVERITY_WARNING = 'warning';
    final public const SEVERITY_ERROR = 'error';

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

    public string $codeMajor;
    public string $severity;
    public ?string $description = null;
    public ?ImsxCodeMinor $codeMinor = null;

    public function __construct(string $major, string $severity, ?ImsxCodeMinor $minor = null, ?string $desc = null)
    {
        $this->codeMajor = $major;
        $this->severity = $severity;
        $this->codeMinor = $minor;
        $this->description = $desc;
    }
}
