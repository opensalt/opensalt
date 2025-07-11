<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use App\Repository\Framework\ImportLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ImportLogRepository::class)]
#[ORM\Table(name: 'import_logs')]
class ImportLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'is_read', type: Types::BOOLEAN, nullable: false, options: ['default' => 0])]
    public bool $read = false;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: LsDoc::class, inversedBy: 'importLogs')]
        #[ORM\JoinColumn(name: 'ls_doc_id', referencedColumnName: 'id', nullable: false)]
        #[Assert\NotBlank]
        public readonly ?LsDoc $lsDoc = null,
        #[ORM\Column(name: 'message_type', type: Types::STRING, length: 30)]
        public readonly string $messageType = 'warning',
        #[ORM\Column(name: 'message_text', type: Types::STRING, length: 250)]
        #[Assert\NotBlank]
        public readonly ?string $message = null,
    ) {
    }
}
