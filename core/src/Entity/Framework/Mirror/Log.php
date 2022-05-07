<?php

namespace App\Entity\Framework\Mirror;

use App\Repository\Framework\Mirror\LogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'mirror_log')]
#[ORM\Entity(repositoryClass: LogRepository::class)]
class Log
{
    final public const STATUS_SUCCESS = 'success';
    final public const STATUS_FAILURE = 'failure';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Framework::class, inversedBy: 'logs')]
    #[ORM\JoinColumn(nullable: false)]
    private Framework $mirror;

    #[ORM\Column(name: 'status', type: 'string')]
    private string $status;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'datetime', precision: 6)]
    private \DateTimeInterface $occurredAt;

    public function __construct(Framework $mirror, string $status, string $message)
    {
        $this->mirror = $mirror;
        $this->status = $status;
        $this->message = $message;
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMirror(): Framework
    {
        return $this->mirror;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getOccurredAt(): \DateTimeInterface
    {
        return $this->occurredAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
