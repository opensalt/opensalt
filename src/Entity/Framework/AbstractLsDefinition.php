<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * AbstractLsDefinition
 *
 * @ORM\MappedSuperclass()
 *
 * @Serializer\ExclusionPolicy("all")
 */
class AbstractLsDefinition extends AbstractLsBase
{
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     *
     * @Serializer\Expose()
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     *
     * @Serializer\Expose()
     */
    private $description;


    /**
     * Set title
     *
     * @param string $title
     *
     * @return AbstractLsDefinition
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return AbstractLsDefinition
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
