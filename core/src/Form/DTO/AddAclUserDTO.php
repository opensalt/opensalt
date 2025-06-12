<?php

declare(strict_types=1);

namespace App\Form\DTO;

use App\Entity\Framework\LsDoc;
use App\Entity\User\User;
use Symfony\Component\Validator\Constraints as Assert;

class AddAclUserDTO
{
    public function __construct(
        #[Assert\Type(LsDoc::class)]
        #[Assert\NotNull]
        public LsDoc $lsDoc,
        #[Assert\Type('int')]
        #[Assert\NotNull]
        public int $access,
        #[Assert\Type(User::class)]
        #[Assert\NotNull]
        public ?User $user = null,
    ) {
    }
}
