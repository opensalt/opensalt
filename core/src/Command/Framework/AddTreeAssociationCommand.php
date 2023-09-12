<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\AssociationSubtype;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AddTreeAssociationCommand extends BaseCommand
{
    #[Assert\Type(LsDoc::class)]
    #[Assert\NotNull]
    private LsDoc $doc;

    #[Assert\Type('string')]
    #[Assert\NotNull]
    private string $type;

    #[Assert\Type('array')]
    #[Assert\NotNull]
    #[Assert\Collection(
        fields: [
            'id' => new Assert\Type('string'),
            'identifier' => new Assert\Type('string'),
            'uri' => new Assert\Type('string'),
            'externalDoc' => new Assert\Type('string'),
        ],
        allowMissingFields: true,
    )]
    private array $origin;

    #[Assert\Type('array')]
    #[Assert\NotNull]
    #[Assert\Collection(
        fields: [
            'id' => new Assert\Type('string'),
            'identifier' => new Assert\Type('string'),
            'uri' => new Assert\Type('string'),
            'externalDoc' => new Assert\Type('string'),
        ],
        allowMissingFields: true,
    )]
    private array $dest;

    #[Assert\Type('string')]
    private ?string $assocGroup;

    #[Assert\Type('string')]
    private ?string $annotation;

    /**
     * @var LsAssociation|null
     */
    #[Assert\Type(LsAssociation::class)]
    private $association;

    /**
     * @var array<AssociationSubtype>
     */
    private $allowedSubtypes = [];

    /**
     * Constructor.
     */
    public function __construct(LsDoc $doc, array $origin, string $type, array $dest, ?string $assocGroup = null, ?string $annotation = null)
    {
        $this->doc = $doc;
        $this->type = $type;
        $this->origin = $origin;
        $this->dest = $dest;
        $this->assocGroup = $assocGroup;
        $this->annotation = $annotation;
    }

    public function getDoc(): LsDoc
    {
        return $this->doc;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getOrigin(): array
    {
        return $this->origin;
    }

    public function getDestination(): array
    {
        return $this->dest;
    }

    public function getAssociation(): ?LsAssociation
    {
        return $this->association;
    }

    public function setAssociation(?LsAssociation $association): void
    {
        $this->association = $association;
    }

    public function getAssocGroup(): ?string
    {
        return $this->assocGroup;
    }

    public function getAnnotation(): ?string
    {
        return $this->annotation;
    }

    public function setAllowedSubtypes(array $subtypes): void
    {
        $this->allowedSubtypes = $subtypes;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if (empty($this->origin['id']) && empty($this->origin['identifier'])) {
            $context->buildViolation('One of id or identifier must be supplied for the origin.')
                ->atPath('origin')
                ->addViolation();
        }

        if (empty($this->dest['id']) && empty($this->dest['identifier'])) {
            $context->buildViolation('One of id or identifier must be supplied for the destination.')
                ->atPath('dest')
                ->addViolation();
        }

        $types = explode('|', $this->type, 2);
        if (!in_array($types[0], LsAssociation::allTypes(), true)) {
            $context->buildViolation('Invalid association type supplied.')
                ->atPath('type')
                ->addViolation();
        }

        if (null !== ($types[1] ?? null)) {
            $subtypeValid = false;

            foreach ($this->allowedSubtypes as $allowedSubtype) {
                if (($allowedSubtype->getName() === $types[1]) && ($allowedSubtype->getParentType() === $types[0])) {
                    $subtypeValid = true;

                    break;
                }
            }

            if (!$subtypeValid) {
                $context->buildViolation('Invalid subtype supplied.')
                    ->atPath('type')
                    ->addViolation();
            }
        }
    }
}
