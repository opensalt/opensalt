<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use App\Entity\User\User;
use Symfony\Component\Validator\Constraints as Assert;

class LockDocumentCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsDoc::class)]
        #[Assert\NotNull]
        private readonly LsDoc $doc,
        private readonly User $user,
    ) {
    }

    public function getDoc(): LsDoc
    {
        return $this->doc;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
