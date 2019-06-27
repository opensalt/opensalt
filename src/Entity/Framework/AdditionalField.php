<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Framework\AdditionalFieldRepository")
 * @ORM\Table(
 *     name="salt_additional_field",
 *     indexes={
 *         @ORM\Index(name="applies_idx", columns={"applies_to"})
 *     }
 * )
 * @UniqueEntity(fields={"name"}, message="The custom field name is already used.")
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
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @Assert\Regex(pattern="/^[a-z0-9_]+$/", message="The name may only have lower case alpha-numeric characters and underscores.")
     * @Assert\Regex(pattern="/(^_)|(_$)|(_[^a-z])/", match=false, message="An underscore (_) must be before a letter (not a number or underscore), and must not be at the beginning of the name.")
     * @Assert\Regex(pattern="/^[a-z]/", match=true, message="The name must start with a lower case letter.")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull()
     * @Assert\NotBlank()
     */
    private $appliesTo;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull()
     * @Assert\NotBlank()
     */
    private $displayName;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull()
     * @Assert\Choice(callback="getTypes", message="The field type is not a valid type.")
     */
    private $type;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $typeInfo;

    /**
     * Return the valid field types available.
     */
    public static function getTypes(): array
    {
        return [
            'string',
        ];
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get appliesTo.
     */
    public function getAppliesTo(): ?string
    {
        return $this->appliesTo;
    }

    /**
     * Set appliesTo.
     */
    public function setAppliesTo(string $appliesTo): void
    {
        $this->appliesTo = $appliesTo;
    }

    /**
     * Get displayName.
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /**
     * Set displayName.
     */
    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    /**
     * Get type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set type.
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get typeInfo.
     */
    public function getTypeInfo(): ?array
    {
        return $this->typeInfo;
    }

    /**
     * Set typeInfo.
     *
     * @param array|null $typeInfo Used to define how different field types need to behave
     */
    public function setTypeInfo(?array $typeInfo): void
    {
        $this->typeInfo = $typeInfo;
    }
}
