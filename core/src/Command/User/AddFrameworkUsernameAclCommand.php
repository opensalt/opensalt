<?php

namespace App\Command\User;

use App\Command\BaseCommand;
use App\Form\DTO\AddAclUsernameDTO;

class AddFrameworkUsernameAclCommand extends BaseCommand
{
    /**
     * @var AddAclUsernameDTO
     */
    private $dto;

    public function __construct(AddAclUsernameDTO $dto)
    {
        $this->dto = $dto;
    }


    public function getDto(): AddAclUsernameDTO
    {
        return $this->dto;
    }
}
