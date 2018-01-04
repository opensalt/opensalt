<?php

namespace App\Command\Import;

use App\Command\BaseCommand;
use Salt\UserBundle\Entity\Organization;
use Symfony\Component\Validator\Constraints as Assert;

class ImportAsnFromUrlCommand extends BaseCommand
{
    /**
     * @var string
     *
     * @Assert\NotNull()
     * @Assert\NotBlank()
     */
    private $asnIdOrUrl;

    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var string
     */
    private $creator;

    public function __construct(string $asnIdOrUrl, ?string $creator = null, ?Organization $organization = null)
    {
        $this->asnIdOrUrl = $asnIdOrUrl;
        $this->organization = $organization;
        $this->creator = $creator;
    }

    public function getAsnIdOrUrl(): string
    {
        return $this->asnIdOrUrl;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }
}
