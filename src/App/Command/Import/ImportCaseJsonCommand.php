<?php

namespace App\Command\Import;

use App\Command\BaseCommand;
use Salt\UserBundle\Entity\Organization;
use Symfony\Component\Validator\Constraints as Assert;

class ImportCaseJsonCommand extends BaseCommand
{
    /**
     * @var \stdClass
     *
     * @Assert\NotNull()
     */
    private $caseJson;

    /**
     * @var Organization
     */
    private $organization;

    public function __construct(\stdClass $caseJson, ?Organization $organization = null)
    {
        $this->caseJson = $caseJson;
        $this->organization = $organization;
    }

    public function getCaseJson(): \stdClass
    {
        return $this->caseJson;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }
}
