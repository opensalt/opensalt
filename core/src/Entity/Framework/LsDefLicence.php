<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;

/**
 * LsDefLicence
 *
 * @ORM\Table(name="ls_def_licence")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\LsDefLicenceRepository")
 */
class LsDefLicence extends AbstractLsDefinition implements CaseApiInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="licence_text", type="text")
     */
    private $licenceText;

    /**
     * @return string
     */
    public function getLicenceText()
    {
        return $this->licenceText;
    }

    /**
     * @param string $licenceText
     *
     * @return LsDefLicence
     */
    public function setLicenceText($licenceText)
    {
        $this->licenceText = $licenceText;

        return $this;
    }
}
