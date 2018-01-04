<?php

namespace App\Command\User;

use App\Command\BaseCommand;
use Salt\UserBundle\Form\DTO\AddAclUserDTO;
use Symfony\Component\Validator\Constraints as Assert;

class AddFrameworkUserAclCommand extends BaseCommand
{
    /**
     * @var AddAclUserDTO
     *
     * @Assert\Type(AddAclUserDTO::class)
     * @Assert\NotNull()
     */
    private $dto;

    public function __construct(AddAclUserDTO $dto)
    {
        $this->dto = $dto;
    }

    /**
     * @return AddAclUserDTO
     */
    public function getDto(): AddAclUserDTO
    {
        return $this->dto;
    }
}
