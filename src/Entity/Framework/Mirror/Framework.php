<?php

namespace App\Entity\Framework\Mirror;

use App\Entity\Framework\LsDoc;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Swaggest\JsonDiff\JsonDiff;

/**
 * @ORM\Table(name="mirror_framework")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\Mirror\FrameworkRepository")
 */
class Framework
{
    public const STATUS_NEW = 'new';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_OK = 'ok';
    public const STATUS_ERROR = 'error';

    public const ERROR_GENERAL = 'general';
    public const ERROR_ID_CONFLICT = 'identifier_conflict';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Server
     *
     * @ORM\ManyToOne(targetEntity="Server", inversedBy="frameworks")
     */
    private $server;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", nullable=false)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="identifier", type="string", nullable=false)
     */
    private $identifier;

    /**
     * @var string|null
     *
     * @ORM\Column(name="creator", type="string", nullable=true)
     */
    private $creator;

    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    private $title;

    /**
     * @var LsDoc|null
     *
     * @ORM\OneToOne(targetEntity="App\Entity\Framework\LsDoc", mappedBy="mirroredFramework")
     */
    private $framework;

    /**
     * @var bool
     *
     * @ORM\Column(name="include", type="boolean", nullable=false)
     */
    private $include = true;

    /**
     * @var int
     * @ORM\Column(name="priority", type="integer", options={"default": 0})
     */
    private $priority = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", options={"default": "new"})
     */
    private $status = self::STATUS_NEW;

    /**
     * @var int
     *
     * @ORM\Column(name="status_count", type="integer", nullable=false, options={"default": 0})
     */
    private $statusCount = 0;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="last_check", type="datetime", nullable=true)
     */
    private $lastCheck;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="last_success", type="datetime", nullable=true)
     */
    private $lastSuccess;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="last_failure", type="datetime", nullable=true)
     */
    private $lastFailure;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="last_change", type="datetime", nullable=true)
     */
    private $lastChange;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="next_check", type="datetime", nullable=true)
     */
    private $nextCheck;

    /**
     * @var string|null
     *
     * @ORM\Column(name="error_type", type="string", nullable=true)
     */
    private $errorType;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="updated_at", type="datetime", precision=6)
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @var string|resource
     *
     * @ORM\Column(name="last_content", type="blob", nullable=true)
     */
    private $lastContent;

    /**
     * @var string|resource
     *
     * @ORM\Column(name="last_success_content", type="blob", nullable=true)
     */
    private $lastSuccessContent;

    /**
     * @var Log[]|Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Framework\Mirror\Log", mappedBy="mirror", orphanRemoval=true, cascade={"persist"})
     */
    private $logs;

    public function __construct(Server $server, string $identifier)
    {
        $this->server = $server;
        $this->identifier = $identifier;
        $this->logs = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function isInclude(): bool
    {
        return $this->include;
    }

    public function setInclude(bool $include): self
    {
        $this->include = $include;

        return $this;
    }

    public function getStatusCount(): int
    {
        return $this->statusCount;
    }

    public function getFramework(): ?LsDoc
    {
        return $this->framework;
    }

    public function setFramework(?LsDoc $framework): self
    {
        $this->framework = $framework;

        return $this;
    }

    public function getLastCheck(): ?\DateTimeInterface
    {
        return $this->lastCheck;
    }

    public function getLastChange(): ?\DateTimeInterface
    {
        return $this->lastChange;
    }

    public function getNextCheck(): ?\DateTimeInterface
    {
        return $this->nextCheck;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function isIdConflicted(): bool
    {
        return self::STATUS_ERROR === $this->status && self::ERROR_ID_CONFLICT === $this->errorType;
    }

    public function setErrorType(?string $errorType): self
    {
        $this->errorType = $errorType;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getTitle(): string
    {
        return $this->title ?? 'Unknown';
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getCreator(): string
    {
        return $this->creator ?? 'Unknown';
    }

    public function setCreator(string $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getLastSuccessContent(): ?string
    {
        if (null === $this->lastSuccessContent) {
            return null;
        }

        if (is_string($this->lastSuccessContent)) {
            return bzdecompress($this->lastSuccessContent);
        }

        return bzdecompress(stream_get_contents($this->lastSuccessContent));
    }

    public function setLastSuccessContent(?string $lastSuccessContent): self
    {
        if (null === $lastSuccessContent) {
            $this->lastSuccessContent = null;
            $this->lastContent = null;

            return $this;
        }

        $this->lastSuccessContent = bzcompress($lastSuccessContent, 9);
        $this->lastContent = $this->lastSuccessContent;

        return $this;
    }

    public function getLastContent(): ?string
    {
        if (null === $this->lastContent) {
            return null;
        }

        if (is_string($this->lastContent)) {
            return bzdecompress($this->lastContent);
        }

        return bzdecompress(stream_get_contents($this->lastContent));
    }

    public function setLastContent(?string $lastContent): self
    {
        if (null === $lastContent) {
            $this->lastContent = null;

            return $this;
        }

        $this->lastContent = bzcompress($lastContent, 9);

        return $this;
    }

    /**
     * @return Log[]|Collection
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(string $status, string $message): Log
    {
        $log = new Log($this, $status, $message);
        $this->logs[] = $log;

        return $log;
    }

    public function clearLogs(): self
    {
        $this->logs->clear();

        return $this;
    }

    public function markToRefresh(): self
    {
        $this->nextCheck = new \DateTimeImmutable();
        $this->priority = 1000;
        $this->status = self::STATUS_SCHEDULED;

        return $this;
    }

    public function markSuccess(bool $changed = false): self
    {
        $this->errorType = null;
        $this->status = self::STATUS_OK;
        ++$this->statusCount;
        if ($changed || null === $this->lastSuccess || (null !== $this->lastFailure && $this->lastFailure > $this->lastSuccess)) {
            $this->statusCount = 1;
        }

        $this->lastSuccess = new \DateTimeImmutable();
        $this->lastCheck = $this->lastSuccess;
        $this->scheduleNextCheck($this->lastSuccess, (int) (86400 * 1.1855 ** min(20, $this->statusCount - 1)));

        if ($changed) {
            $this->lastChange = $this->lastSuccess;
        }

        return $this;
    }

    public function markFailure(?string $errorType = self::ERROR_GENERAL): self
    {
        $this->errorType = $errorType;
        $this->status = self::STATUS_ERROR;
        ++$this->statusCount;
        if (null === $this->lastFailure || (null !== $this->lastSuccess && $this->lastFailure < $this->lastSuccess)) {
            $this->statusCount = 1;
        }

        $this->lastFailure = new \DateTimeImmutable();
        $this->lastCheck = $this->lastFailure;
        $this->scheduleNextCheck($this->lastFailure, (int) (21600 * 1.5 ** min(12, $this->statusCount - 1)));

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    public function matchesLastSuccessContent(?string $framework): bool
    {
        try {
            $diff = new JsonDiff(
                json5_decode($this->getLastSuccessContent()),
                json5_decode($framework),
                JsonDiff::STOP_ON_DIFF + JsonDiff::REARRANGE_ARRAYS
            );
        } catch (\Exception $e) {
            return false;
        }

        return 0 === $diff->getDiffCnt();
    }

    public function hasLogs(): bool
    {
        return !$this->logs->isEmpty();
    }

    public function getLastFailure(): ?\DateTimeInterface
    {
        return $this->lastFailure;
    }

    private function scheduleNextCheck(\DateTimeInterface $when, int $in = 86400, ?int $variance = null): void
    {
        if (null === $variance) {
            $variance = (int) ($in / 2);
        }

        $ts = $when->getTimestamp() + $in;

        // Max +/- 1 week
        $halfVariance = min(604800, (int) ($variance / 2));

        // Check again (default in 18 to 30 hours for first check)
        $nextCheck = new \DateTimeImmutable('@'.random_int($ts - $halfVariance, $ts + $halfVariance));
        $this->nextCheck = $nextCheck;

        $this->priority = 0;
    }
}
