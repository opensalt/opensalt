<?php

namespace App\Command\User;

use App\Command\BaseCommand;
use Salt\UserBundle\Entity\Organization;
use Symfony\Component\Validator\Constraints as Assert;

class AddUserByNameCommand extends BaseCommand
{
    /**
     * @var string
     *
     * @Assert\NotNull()
     * @Assert\NotBlank()
     */
    private $userName;

    /**
     * @var Organization
     *
     * @Assert\NotNull()
     */
    private $organization;

    /**
     * @var null|string
     */
    private $plainPassword;

    /**
     * @var null|string
     */
    private $role;

    /**
     * @var string
     */
    private $newPassword;

    public function __construct(string $userName, Organization $organization, ?string $plainPassword = null, ?string $role = null)
    {
        $this->userName = $userName;
        $this->organization = $organization;
        $this->plainPassword = $plainPassword;
        $this->role = $role;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }

    public function setNewPassword(string $newPassword): void
    {
        $this->newPassword = $newPassword;
    }
}
