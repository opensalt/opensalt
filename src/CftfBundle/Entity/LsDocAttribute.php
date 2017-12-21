<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LsDocAttribute
 *
 * @ORM\Table(name="ls_doc_attribute")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\LsDocAttributeRepository")
 */
class LsDocAttribute
{
    public const IS_GRADE_LEVELS = 'isGradeLevels';

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc", inversedBy="attributes")
     */
    private $lsDoc;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="attribute", type="string", length=255)
     */
    private $attribute;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
     */
    private $value;


    public function __construct($lsDoc, $attribute, $value) {
        $this->lsDoc = $lsDoc;
        $this->attribute = $attribute;
        $this->value = $value;
    }

    /**
     * Get lsDoc
     *
     * @return LsDoc
     */
    public function getLsDoc()
    {
        return $this->lsDoc;
    }

    /**
     * Set attribute
     *
     * @param string $attribute
     *
     * @return LsDocAttribute
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Get attribute
     *
     * @return string
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return LsDocAttribute
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
