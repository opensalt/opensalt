<?php

namespace App\Command\Import;

use App\Command\BaseCommand;
use App\Entity\User\Organization;
use App\Entity\User\User;
use Symfony\Component\Validator\Constraints as Assert;

class ImportCaseJsonCommand extends BaseCommand
{
    /**
     * @var string
     */
    #[Assert\NotNull]
    private $caseJson;

    /**
     * @var Organization|null
     */
    private $organization;

    /**
     * @var User|null
     */
    private $user;

    public function __construct(string $caseJson, ?Organization $organization = null, ?User $user = null)
    {
        $this->caseJson = $caseJson;
        $this->organization = $organization;
        $this->user = $user;
    }

    public function getCaseJson(): string
    {
        return $this->caseJson;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
