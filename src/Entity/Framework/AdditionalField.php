<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Framework\AdditionalFieldRepository")
 */
class AdditionalField
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $appliesTo;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $displayName;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\ Column(type="string", nullable=true)
     */
    private $typeInfo;

    public function getId()
    {
      return $this->id;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
      return $this->name;
    }

    /**
     * Set name
     *
     * @param string
     */
    public function setName($name): void
    {
      $this->name = $name;
    }

    /**
     * Get appliesTo
     *
     * @return string
     */
    public function getAppliesTo()
    {
      return $this->appliesTo;
    }

    /**
     * Set appliesTo
     *
     * @param string
     */
    public function setAppliesTo($appliesTo): void
    {
      $this->appliesTo = $appliesTo;
    }

    /**
     * Get displayName
     *
     * @return string
     */
    public function getDisplayName()
    {
      return $this->displayName;
    }

    /**
     * Set displayName
     *
     * @param string
     */
    public function setDisplayName($displayName): void
    {
      $this->displayName = $displayName;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
      return $this->type;
    }

    /**
     * Set type
     *
     * @param string
     */
    public function setType($type): void
    {
      $this->type = $type;
    }

    /**
     * Get typeInfo
     *
     * @return string
     */
    public function getTypeInfo()
    {
      return $this->typeInfo;
    }

    /**
     * Set typeInfo
     *
     * @param string
     */
    public function setTypeInfo($typeInfo): void
    {
      $this->typeInfo = $typeInfo;
    }
}
