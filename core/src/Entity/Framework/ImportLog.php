<?php

declare(strict_types=1);

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
    private ?int $id = null;

    #[ORM\Column(name: 'is_read', type: 'boolean', nullable: false, options: ['default' => 0])]
    public private(set) bool $read = false {
        get => $this->read;
    }

    public function __construct(
        #[ORM\ManyToOne(targetEntity: LsDoc::class, inversedBy: 'importLogs')]
        #[ORM\JoinColumn(name: 'ls_doc_id', referencedColumnName: 'id', nullable: false)]
        #[Assert\NotBlank]
        public private(set) ?LsDoc $lsDoc = null {
            get => $this->lsDoc;
        },
        #[ORM\Column(name: 'message_type', type: 'string', length: 30)]
        public private(set) string $messageType = 'warning' {
            get => $this->messageType;
        },
        #[ORM\Column(name: 'message_text', type: 'string', length: 250)]
        #[Assert\NotBlank]
        public private(set) ?string $message = null {
            get => $this->message;
        },
    ) {
    }

    public function markAsRead(): void
    {
        $this->read = true;
    }
}
