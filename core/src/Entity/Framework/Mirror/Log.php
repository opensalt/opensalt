<?php

namespace App\Entity\Framework\Mirror;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="mirror_log")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\Mirror\LogRepository")
 */
class Log
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILURE = 'failure';

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Framework
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Framework\Mirror\Framework", inversedBy="logs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $mirror;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime", precision=6)
     */
    private $occurredAt;

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

    public function getOccurredAt(): ?\DateTimeInterface
    {
        return $this->occurredAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
