<?php

namespace App\Entity\Framework\Mirror;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="mirror_server")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\Mirror\ServerRepository")
 */
class Server
{
    public const TYPE_CASE_1_0 = 'CASE/1.0';
    public const TYPE_DIRECT = 'Direct URI/0.0';

    public const URL_CASE_1_0_LIST = '/ims/case/v1p0/CFDocuments';
    public const URL_CASE_1_0_PACKAGE = '/ims/case/v1p0/CFPackages';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", nullable=false)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="api_type", type="string", nullable=false)
     */
    private $serverType = self::TYPE_CASE_1_0;

    /**
     * @var bool
     * @ORM\Column(name="check_server", type="boolean", nullable=false)
     */
    private $checkServer = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="add_found", type="boolean", nullable=false)
     */
    private $addFoundFrameworks;

    /**
     * @var OAuthCredential|null
     *
     * @ORM\ManyToOne(targetEntity="OAuthCredential")
     */
    private $credentials;

    /**
     * @var int
     * @ORM\Column(name="priority", type="integer", options={"default": 0})
     */
    private $priority = 0;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="next_check", type="datetime", nullable=true)
     */
    private $nextCheck;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="last_check", type="datetime", nullable=true)
     */
    private $lastCheck;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="updated_at", type="datetime", precision=6)
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @var array<Framework>|Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Framework\Mirror\Framework", mappedBy="server")
     */
    private $frameworks;

    public function __construct(string $hostname, bool $addFoundFrameworks, ?OAuthCredential $credentials = null)
    {
        $this->url = $hostname;
        $this->addFoundFrameworks = $addFoundFrameworks;
        $this->credentials = $credentials;
        $this->frameworks = new ArrayCollection();
    }

    public static function makeSingleFrameworkServer(string $hostname, ?OAuthCredential $credentials = null): self
    {
        $server = new self($hostname, false, $credentials);
        $server->setCheckServer(false);

        return $server;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getServerType(): string
    {
        return $this->serverType;
    }

    public function setServerType(string $serverType): self
    {
        $this->serverType = $serverType;

        return $this;
    }

    public function isAddFoundFrameworks(): bool
    {
        return $this->addFoundFrameworks;
    }

    public function setAddFoundFrameworks(bool $addFoundFrameworks): self
    {
        $this->addFoundFrameworks = $addFoundFrameworks;

        return $this;
    }

    public function getCredentials(): ?OAuthCredential
    {
        return $this->credentials;
    }

    public function setCredentials(?OAuthCredential $credentials): self
    {
        $this->credentials = $credentials;

        return $this;
    }

    public function getNextCheck(): ?\DateTimeInterface
    {
        return $this->nextCheck;
    }

    public function setNextCheck(?\DateTimeInterface $nextCheck): self
    {
        $this->nextCheck = $nextCheck;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection|Framework[]
     */
    public function getFrameworks(): Collection
    {
        return $this->frameworks;
    }

    public function isCheckServer(): bool
    {
        return $this->checkServer;
    }

    public function setCheckServer(bool $checkServer): self
    {
        $this->checkServer = $checkServer;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getLastCheck(): ?\DateTimeInterface
    {
        return $this->lastCheck;
    }

    public function setLastCheck(\DateTimeInterface $lastCheck): self
    {
        $this->lastCheck = $lastCheck;

        return $this;
    }

    public function scheduleNextCheck(?\DateTimeInterface $when = null, int $in = 604800, ?int $variance = null): void
    {
        if (null === $variance) {
            $variance = (int) ($in / 2);
        }

        if (null === $when) {
            $when = new \DateTimeImmutable();
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
