<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AddTreeAssociationCommand extends BaseCommand
{
    /**
     * @var LsDoc
     *
     * @Assert\Type(LsDoc::class)
     * @Assert\NotNull()
     */
    private $doc;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotNull()
     */
    private $type;

    /**
     * @var array
     *
     * @Assert\Type("array")
     * @Assert\NotNull()
     * @Assert\Collection(
     *     fields = {
     *         "id" = {
     *             @Assert\Type("string")
     *         },
     *         "identifier" = {
     *             @Assert\Type("string")
     *         },
     *         "externalDoc" = {
     *             @Assert\Type("string")
     *         }
     *     },
     *     allowMissingFields = true
     * )
     */
    private $origin;

    /**
     * @var array
     *
     * @Assert\Type("array")
     * @Assert\NotNull()
     * @Assert\Collection(
     *     fields = {
     *         "id" = {
     *             @Assert\Type("string")
     *         },
     *         "identifier" = {
     *             @Assert\Type("string")
     *         },
     *         "externalDoc" = {
     *             @Assert\Type("string")
     *         }
     *     },
     *     allowMissingFields = true
     * )
     */
    private $dest;

    /**
     * @var string
     *
     * @Assert\Type("string")
     */
    private $assocGroup;

    /**
     * @var LsAssociation|null
     *
     * @Assert\Type(LsAssociation::class)
     */
    private $association;

    /**
     * Constructor.
     */
    public function __construct(LsDoc $doc, array $origin, string $type, array $dest, ?string $assocGroup = null)
    {
        $this->doc = $doc;
        $this->type = $type;
        $this->origin = $origin;
        $this->dest = $dest;
        $this->assocGroup = $assocGroup;
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

    /**
     * @Assert\Callback()
     */
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

        if (!in_array($this->type, LsAssociation::allTypes(), true)) {
            $context->buildViolation('Invalid association type supplied.')
                ->atPath('type')
                ->addViolation();
        }
    }
}
