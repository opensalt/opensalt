<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use App\Entity\User\User;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteFrameworkAclCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsDoc::class)]
        #[Assert\NotNull]
        private readonly LsDoc $doc,
        #[Assert\Type(User::class)]
        #[Assert\NotNull]
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
