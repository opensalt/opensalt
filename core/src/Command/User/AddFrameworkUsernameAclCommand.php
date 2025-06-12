<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Command\BaseCommand;
use App\Form\DTO\AddAclUsernameDTO;

class AddFrameworkUsernameAclCommand extends BaseCommand
{
    public function __construct(private readonly AddAclUsernameDTO $dto)
    {
    }

    public function getDto(): AddAclUsernameDTO
    {
        return $this->dto;
    }
}
