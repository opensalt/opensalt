<?php

namespace App\Entity\Framework;

use App\Repository\Framework\ImportLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ImportLogRepository::class)]
#[ORM\Table(name: 'import_logs')]
#[UniqueEntity('id')]
class ImportLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id;

    #[ORM\ManyToOne(targetEntity: LsDoc::class, inversedBy: 'importLogs')]
    #[ORM\JoinColumn(name: 'ls_doc_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotBlank]
    protected ?LsDoc $lsDoc;

    #[ORM\Column(name: 'message_text', type: 'string', length: 250)]
    #[Assert\NotBlank]
    protected ?string $message;

    #[ORM\Column(name: 'message_type', type: 'string', length: 30, nullable: false)]
    protected string $messageType;

    #[ORM\Column(name: 'is_read', type: 'boolean', nullable: false, options: ['default' => 0])]
    protected bool $read = false;

    public function __construct()
    {
        $this->messageType = 'warning';
    }

    public function setLsDoc(LsDoc $lsDoc): void
    {
        $this->lsDoc = $lsDoc;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function isRead(): bool
    {
        return $this->read;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function markAsRead(): void
    {
        $this->read = true;
    }

    public function getMessageType(): ?string
    {
        return $this->messageType;
    }

    public function setMessageType(string $messageType): void
    {
        $this->messageType = $messageType;
    }
}
