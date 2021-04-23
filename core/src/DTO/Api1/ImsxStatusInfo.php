<?php

namespace App\DTO\Api1;

use JMS\Serializer\Annotation as Serializer;

class ImsxStatusInfo
{
    public const CODE_MAJOR_SUCCESS = 'success';
    public const CODE_MAJOR_PROCESSING = 'processing';
    public const CODE_MAJOR_FAILURE = 'failure';
    public const CODE_MAJOR_UNSUPPORTED = 'unsupported';

    public const SEVERITY_STATUS = 'status';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';

    /**
     * @Serializer\Exclude()
     */
    public static array $codeMajorValues = [
        self::CODE_MAJOR_SUCCESS,
        self::CODE_MAJOR_PROCESSING,
        self::CODE_MAJOR_FAILURE,
        self::CODE_MAJOR_UNSUPPORTED,
    ];

    /**
     * @Serializer\Exclude()
     */
    public static array $severityValues = [
        self::SEVERITY_STATUS,
        self::SEVERITY_WARNING,
        self::SEVERITY_ERROR,
    ];

    /**
     * @Serializer\Expose()
     * @Serializer\SerializedName("imsx_codeMajor")
     */
    public string $codeMajor;

    /**
     * @Serializer\Expose()
     * @Serializer\SerializedName("imsx_severity")
     */
    public string $severity;

    /**
     * @Serializer\Expose()
     * @Serializer\SerializedName("imsx_description")
     */
    public ?string $description = null;

    /**
     * @Serializer\Expose()
     * @Serializer\SerializedName("imsx_codeMinor")
     * @Serializer\Type("App\DTO\Api1\ImsxCodeMinor")
     */
    public ?ImsxCodeMinor $codeMinor = null;

    public function __construct(string $major, string $severity, ?ImsxCodeMinor $minor = null, ?string $desc = null)
    {
        $this->codeMajor = $major;
        $this->severity = $severity;
        $this->codeMinor = $minor;
        $this->description = $desc;
    }
}
