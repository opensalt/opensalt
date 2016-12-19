<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LsDefLicence
 *
 * @ORM\Table(name="ls_def_licence")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\LsDefLicenceRepository")
 */
class LsDefLicence extends AbstractLsDefinition
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
    public function getLicenceText() {
        return $this->licenceText;
    }

    /**
     * @param string $licenceText
     *
     * @return LsDefLicence
     */
    public function setLicenceText($licenceText) {
        $this->licenceText = $licenceText;
        return $this;
    }
}
