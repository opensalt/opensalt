<?php

namespace App\Entity\Framework;

use App\Repository\Framework\LsDefLicenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ls_def_licence')]
#[ORM\Entity(repositoryClass: LsDefLicenceRepository::class)]
class LsDefLicence extends AbstractLsDefinition implements CaseApiInterface
{
    #[ORM\Column(name: 'licence_text', type: 'text')]
    private string $licenceText;

    public function getLicenceText(): string
    {
        return $this->licenceText;
    }

    public function setLicenceText(string $licenceText): void
    {
        $this->licenceText = $licenceText;
    }
}
