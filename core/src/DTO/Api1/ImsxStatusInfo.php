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

    final public const array CODE_MAJOR_VALUES = [
        self::CODE_MAJOR_SUCCESS,
        self::CODE_MAJOR_PROCESSING,
        self::CODE_MAJOR_FAILURE,
        self::CODE_MAJOR_UNSUPPORTED,
    ];

    final public const array SEVERITY_VALUES = [
        self::SEVERITY_STATUS,
        self::SEVERITY_WARNING,
        self::SEVERITY_ERROR,
    ];

    public function __construct(
        public string $codeMajor,
        public string $severity,
        public ?ImsxCodeMinor $codeMinor = null,
        public ?string $description = null,
    ) {
        if (!in_array($this->codeMajor, self::CODE_MAJOR_VALUES, true)) {
            throw new \InvalidArgumentException(sprintf('Value %s is invalid.', $this->codeMajor));
        }

        if (!in_array($this->severity, self::SEVERITY_VALUES, true)) {
            throw new \InvalidArgumentException(sprintf('Value %s is invalid.', $this->severity));
        }
    }
}
