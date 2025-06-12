<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Command\BaseCommand;
use App\Form\DTO\AddAclUserDTO;
use Symfony\Component\Validator\Constraints as Assert;

class AddFrameworkUserAclCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(AddAclUserDTO::class)]
        #[Assert\NotNull]
        private readonly AddAclUserDTO $dto,
    ) {
    }

    public function getDto(): AddAclUserDTO
    {
        return $this->dto;
    }
}
