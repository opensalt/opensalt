<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use App\Repository\Framework\LsAssociationRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'ls_association')]
#[ORM\Index(name: 'dest_id_idx', columns: ['destination_node_identifier'])]
#[ORM\Index(name: 'orig_id_idx', columns: ['origin_node_identifier'])]
#[ORM\Entity(repositoryClass: LsAssociationRepository::class)]
#[ORM\UniqueConstraint(name: 'ls_association_identifier', columns: ['identifier', 'ls_doc_identifier'])]
#[ORM\UniqueConstraint(name: 'ls_association_uri', columns: ['uri', 'ls_doc_identifier'])]
#[ORM\AttributeOverrides([
    new ORM\AttributeOverride('identifier', new ORM\Column(
        name: 'identifier',
        length: 300,
        unique: false,
    )),
    new ORM\AttributeOverride('uri', new ORM\Column(
        name: 'uri',
        length: 300,
        unique: false,
        nullable: true,
    )),
])]
class LsAssociation implements CaseApiInterface
{
    use IdentifiableTrait;
    use ChangedAtTrait;
    use CloneIdentifiableTrait;
    use ExtraDataTrait;
    use ExtensionTrait;
    use AccessAdditionalFieldTrait;

    final public const string CHILD_OF = 'isChildOf';

    final public const string EXACT_MATCH_OF = 'exactMatchOf';
    final public const string RELATED_TO = 'isRelatedTo';
    final public const string PART_OF = 'isPartOf';
    final public const string REPLACED_BY = 'replacedBy';
    final public const string PRECEDES = 'precedes';
    final public const string SKILL_LEVEL = 'hasSkillLevel';
    final public const string IS_PEER_OF = 'isPeerOf';
    final public const string IS_TRANSLATION_OF = 'isTranslationOf';

    final public const string EXEMPLAR = 'exemplar';

    final public const string INVERSE_CHILD_OF = 'isParentOf';

    final public const string INVERSE_EXACT_MATCH_OF = 'matchedFrom';
    final public const string INVERSE_RELATED_TO = 'relatedFrom';
    final public const string INVERSE_PART_OF = 'hasPart';
    final public const string INVERSE_REPLACED_BY = 'replaces';
    final public const string INVERSE_PRECEDES = 'hasPredecessor';
    final public const string INVERSE_SKILL_LEVEL = 'skillLevelFor';
    final public const string INVERSE_IS_PEER_OF = 'peerOf';
    final public const string INVERSE_IS_TRANSLATION_OF = 'translationOf';

    final public const string INVERSE_EXEMPLAR = 'exemplarFor';

    final public const array BASE_TYPES = [
        self::CHILD_OF,
        self::RELATED_TO,
        self::EXACT_MATCH_OF,
        self::PART_OF,
        self::REPLACED_BY,
        self::PRECEDES,
        self::SKILL_LEVEL,
        self::IS_PEER_OF,
        self::EXEMPLAR,
        self::IS_TRANSLATION_OF, // CASE 1.1
    ];

    final public const array HUMAN_READABLE_TYPES = [
        self::CHILD_OF => 'Is Child Of',
        self::EXACT_MATCH_OF => 'Exact Match Of',
        self::RELATED_TO => 'Is Related To',
        self::PART_OF => 'Is Part Of',
        self::REPLACED_BY => 'Replaced By',
        self::PRECEDES => 'Precedes',
        self::SKILL_LEVEL => 'Has Skill Level',
        self::IS_PEER_OF => 'Is Peer Of',
        self::IS_TRANSLATION_OF => 'Is Translation Of',
        self::EXEMPLAR => 'Exemplar',
        self::INVERSE_CHILD_OF => 'Is Parent Of',
        self::INVERSE_EXACT_MATCH_OF => 'Matched From',
        self::INVERSE_RELATED_TO => 'Related From',
        self::INVERSE_PART_OF => 'Has Part',
        self::INVERSE_REPLACED_BY => 'Replaces',
        self::INVERSE_PRECEDES => 'Has Predecessor',
        self::INVERSE_SKILL_LEVEL => 'Skill Level For',
        self::INVERSE_IS_PEER_OF => 'Peer Of',
        self::INVERSE_IS_TRANSLATION_OF => 'Translation Of',
        self::INVERSE_EXEMPLAR => 'Exemplar For',
    ];

    #[ORM\Column(name: 'ls_doc_identifier', type: 'string', length: 300, nullable: false)]
    #[Assert\Length(max: 300)]
    private ?string $lsDocIdentifier = null;

    #[ORM\Column(name: 'ls_doc_uri', type: 'string', length: 300, nullable: true)]
    #[Assert\Length(max: 300)]
    private ?string $lsDocUri = null;

    #[ORM\ManyToOne(targetEntity: LsDoc::class, inversedBy: 'docAssociations')]
    private ?LsDoc $lsDoc = null;

    #[ORM\ManyToOne(targetEntity: LsDefAssociationGrouping::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'assoc_group_id', referencedColumnName: 'id')]
    private ?LsDefAssociationGrouping $group = null;

    #[ORM\Column(name: 'origin_node_identifier', type: 'string', length: 300, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 300)]
    private ?string $originNodeIdentifier = null;

    #[ORM\Column(name: 'origin_node_uri', type: 'string', length: 300, nullable: true)]
    private ?string $originNodeUri = null;

    #[ORM\Column(name: 'origin_node_target_type', type: 'string', length: 300, nullable: true)]
    private ?string $originNodeTargetType = null;

    #[ORM\ManyToOne(targetEntity: LsDoc::class, inversedBy: 'associations')]
    #[ORM\JoinColumn(name: 'origin_lsdoc_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?LsDoc $originLsDoc = null;

    #[ORM\ManyToOne(targetEntity: LsItem::class, cascade: ['persist'], inversedBy: 'associations')]
    #[ORM\JoinColumn(name: 'origin_lsitem_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?LsItem $originLsItem = null;

    #[ORM\Column(name: 'destination_node_identifier', type: 'string', length: 300, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 300)]
    private ?string $destinationNodeIdentifier = null;

    #[ORM\Column(name: 'destination_node_uri', type: 'string', length: 300, nullable: true)]
    private ?string $destinationNodeUri = null;

    #[ORM\Column(name: 'destination_node_target_type', type: 'string', length: 300, nullable: true)]
    private ?string $destinationNodeTargetType = null;

    #[ORM\ManyToOne(targetEntity: LsDoc::class, inversedBy: 'inverseAssociations')]
    #[ORM\JoinColumn(name: 'destination_lsdoc_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?LsDoc $destinationLsDoc = null;

    #[ORM\ManyToOne(targetEntity: LsItem::class, cascade: ['persist'], inversedBy: 'inverseAssociations')]
    #[ORM\JoinColumn(name: 'destination_lsitem_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?LsItem $destinationLsItem = null;

    #[ORM\Column(name: 'type', type: 'string', length: 50, nullable: false)]
    private ?string $type = null;

    #[ORM\Column(name: 'seq', type: 'bigint', nullable: true)]
    private string|int|null $sequenceNumber = null; // The ORM returns a string if the number is very large

    #[ORM\Column(name: 'subtype', type: 'string', nullable: true)]
    private ?string $subtype = null;

    #[ORM\Column(name: 'annotation', type: 'text', length: 65534, nullable: true)]
    private ?string $notes = null;

    public function __construct(UuidInterface|string|null $identifier = null)
    {
        $this->setIdentifierOrNew($identifier);

        $this->updatedAt = new \DateTimeImmutable();
        $this->changedAt = $this->updatedAt;
    }

    public function __toString(): string
    {
        return $this->getUri();
    }

    /**
     * Get all types indexed by camel case.
     */
    public static function allTypesForImportFromCSV(): array
    {
        return [
            self::PART_OF => self::HUMAN_READABLE_TYPES[self::PART_OF],
            self::EXEMPLAR => self::HUMAN_READABLE_TYPES[self::EXEMPLAR],
            self::IS_PEER_OF => self::HUMAN_READABLE_TYPES[self::IS_PEER_OF],
            self::PRECEDES => self::HUMAN_READABLE_TYPES[self::PRECEDES],
            self::RELATED_TO => self::HUMAN_READABLE_TYPES[self::RELATED_TO],
            self::REPLACED_BY => self::HUMAN_READABLE_TYPES[self::REPLACED_BY],
            self::SKILL_LEVEL => self::HUMAN_READABLE_TYPES[self::SKILL_LEVEL],
            self::IS_TRANSLATION_OF => self::HUMAN_READABLE_TYPES[self::IS_TRANSLATION_OF],
        ];
    }

    /**
     * Get an array of all association types available.
     */
    public static function allTypes(): array
    {
        return [
            self::RELATED_TO,
            self::EXACT_MATCH_OF,
            self::PART_OF,
            self::REPLACED_BY,
            self::PRECEDES,
            self::SKILL_LEVEL,
            self::IS_PEER_OF,
            self::EXEMPLAR,
            self::IS_TRANSLATION_OF, // CASE 1.1
            self::CHILD_OF,
        ];
    }

    public static function allTypesHumanReadable(): array
    {
        return [
            self::RELATED_TO => self::HUMAN_READABLE_TYPES[self::RELATED_TO],
            self::EXACT_MATCH_OF => self::HUMAN_READABLE_TYPES[self::EXACT_MATCH_OF],
            self::PART_OF => self::HUMAN_READABLE_TYPES[self::PART_OF],
            self::REPLACED_BY => self::HUMAN_READABLE_TYPES[self::REPLACED_BY],
            self::PRECEDES => self::HUMAN_READABLE_TYPES[self::PRECEDES],
            self::SKILL_LEVEL => self::HUMAN_READABLE_TYPES[self::SKILL_LEVEL],
            self::IS_PEER_OF => self::HUMAN_READABLE_TYPES[self::IS_PEER_OF],
            self::EXEMPLAR => self::HUMAN_READABLE_TYPES[self::EXEMPLAR],
            self::IS_TRANSLATION_OF => self::HUMAN_READABLE_TYPES[self::IS_TRANSLATION_OF], // CASE 1.1
            self::CHILD_OF => self::HUMAN_READABLE_TYPES[self::CHILD_OF],
        ];
    }

    /**
     * Get an array of association types that should show in the choice list.
     */
    public static function typeChoiceList(): array
    {
        return [
            self::RELATED_TO => self::HUMAN_READABLE_TYPES[self::RELATED_TO],
            self::EXACT_MATCH_OF => self::HUMAN_READABLE_TYPES[self::EXACT_MATCH_OF],
            self::PART_OF => self::HUMAN_READABLE_TYPES[self::PART_OF],
            self::REPLACED_BY => self::HUMAN_READABLE_TYPES[self::REPLACED_BY],
            self::PRECEDES => self::HUMAN_READABLE_TYPES[self::PRECEDES],
            self::SKILL_LEVEL => self::HUMAN_READABLE_TYPES[self::SKILL_LEVEL],
            self::IS_PEER_OF => self::HUMAN_READABLE_TYPES[self::IS_PEER_OF],
            self::IS_TRANSLATION_OF => self::HUMAN_READABLE_TYPES[self::IS_TRANSLATION_OF], // CASE 1.1
        ];
    }

    /**
     * Get human-readable display name for the association type.
     */
    public function getDisplayType(): string
    {
        return self::HUMAN_READABLE_TYPES[$this->type] ?? $this->type;
    }

    public static function inverseName(string $name): ?string
    {
        static $inverses = [];
        if (0 === count($inverses)) {
            $inverses = [
                self::CHILD_OF => self::INVERSE_CHILD_OF,
                self::EXACT_MATCH_OF => self::INVERSE_EXACT_MATCH_OF,
                self::RELATED_TO => self::INVERSE_RELATED_TO,
                self::PART_OF => self::INVERSE_PART_OF,
                self::REPLACED_BY => self::INVERSE_REPLACED_BY,
                self::PRECEDES => self::INVERSE_PRECEDES,
                self::IS_PEER_OF => self::INVERSE_IS_PEER_OF,
                self::SKILL_LEVEL => self::INVERSE_SKILL_LEVEL,
                self::EXEMPLAR => self::INVERSE_EXEMPLAR,
                self::IS_TRANSLATION_OF => self::INVERSE_IS_TRANSLATION_OF,

                self::INVERSE_CHILD_OF => self::CHILD_OF,
                self::INVERSE_EXACT_MATCH_OF => self::EXACT_MATCH_OF,
                self::INVERSE_RELATED_TO => self::RELATED_TO,
                self::INVERSE_PART_OF => self::PART_OF,
                self::INVERSE_REPLACED_BY => self::REPLACED_BY,
                self::INVERSE_PRECEDES => self::PRECEDES,
                self::INVERSE_IS_PEER_OF => self::IS_PEER_OF,
                self::INVERSE_SKILL_LEVEL => self::SKILL_LEVEL,
                self::INVERSE_EXEMPLAR => self::EXEMPLAR,
                self::INVERSE_IS_TRANSLATION_OF => self::IS_TRANSLATION_OF,
            ];
        }

        if (array_key_exists($name, $inverses)) {
            return $inverses[$name];
        }

        return null;
    }

    /**
     * Set the Origination of the association.
     *
     * @throws \UnexpectedValueException
     */
    public function setOrigin(IdentifiableInterface|string $origin, ?string $identifier = null, ?string $targetType = null): static
    {
        $this->setOriginNodeTargetType($targetType);

        if (is_string($origin)) {
            $this->setOriginNodeUri($origin);
            $this->setOriginNodeIdentifier($identifier ?? $origin);

            return $this;
        }

        if ($origin instanceof LsDoc) {
            $this->setOriginLsDoc($origin);
        } elseif ($origin instanceof LsItem) {
            $this->setOriginLsItem($origin);
        }
        $this->setOriginNodeUri($origin->getUri());
        $this->setOriginNodeIdentifier($identifier ?? $origin->getIdentifier());

        return $this;
    }

    /**
     * Get the Origination of the association.
     */
    public function getOrigin(): string|LsItem|LsDoc|null
    {
        if (null !== $this->getOriginLsDoc()) {
            return $this->getOriginLsDoc();
        }

        if (null !== $this->getOriginLsItem()) {
            return $this->getOriginLsItem();
        }

        if (!in_array($this->getOriginNodeUri(), [null, ''], true)) {
            return $this->getOriginNodeUri();
        }

        return null;
    }

    public function setOriginNodeUri(string $originNodeUri): static
    {
        $this->originNodeUri = $originNodeUri;

        return $this;
    }

    public function getOriginNodeUri(): ?string
    {
        return $this->originNodeUri;
    }

    /**
     * Set the Destination of the association.
     *
     * @throws \UnexpectedValueException
     */
    public function setDestination(IdentifiableInterface|string $destination, ?string $identifier = null, ?string $targetType = null): static
    {
        $this->setDestinationNodeTargetType($targetType);

        if (is_string($destination)) {
            $this->setDestinationNodeUri($destination);
            $this->setDestinationNodeIdentifier($identifier ?? $destination);

            return $this;
        }

        if ($destination instanceof LsDoc) {
            $this->setDestinationLsDoc($destination);
        } elseif ($destination instanceof LsItem) {
            $this->setDestinationLsItem($destination);
        }
        $this->setDestinationNodeUri($destination->getUri());
        $this->setDestinationNodeIdentifier($identifier ?? $destination->getIdentifier());

        return $this;
    }

    /**
     * Get the Destination of the association.
     */
    public function getDestination(): string|LsItem|LsDoc|null
    {
        if (null !== $this->getDestinationLsDoc()) {
            return $this->getDestinationLsDoc();
        }

        if (null !== $this->getDestinationLsItem()) {
            return $this->getDestinationLsItem();
        }

        if (!in_array($this->getDestinationNodeUri(), [null, ''], true)) {
            return $this->getDestinationNodeUri();
        }

        return null;
    }

    public function setDestinationNodeUri(string $destinationNodeUri): static
    {
        $this->destinationNodeUri = $destinationNodeUri;

        return $this;
    }

    public function getDestinationNodeUri(): ?string
    {
        return $this->destinationNodeUri;
    }

    /**
     * Get HumanCodingScheme from DestinationNodeUri.
     */
    public function getHumanCodingSchemeFromDestinationNodeUri(): ?string
    {
        return $this->splitDestinationDataUri()['value'];
    }

    /**
     * Get an array with the information from a data URI.
     */
    public function splitDestinationDataUri(): array
    {
        if (!str_starts_with($this->destinationNodeUri ?? '', 'data:text/x-')) {
            // Not a known data URI format, return the entire uri as the value
            return ['value' => $this->destinationNodeUri];
        }

        $uri = substr($this->destinationNodeUri, 12);
        [$dataString, $encodedValue] = array_pad(explode(',', $uri, 2), 2, null);

        [$textType, $metadataString] = array_pad(explode(';', $dataString, 2), 2, null);

        $metadata = ['textType' => $textType];
        foreach (explode(';', $metadataString ?? '') as $param) {
            [$name, $value] = array_pad(explode('=', $param, 2), 2, null);
            if (null !== $name && '' !== $name) {
                $metadata[$name] = $value ?? true;
            }
        }

        $metadata['value'] = $metadata['base64'] ?? false ? base64_decode($encodedValue) : rawurldecode($encodedValue);

        return $metadata;
    }

    public function setType(string $type): static
    {
        if (str_starts_with($type, 'ext:') && (1 === preg_match('/^ext:[a-zA-Z0-9._-]+$/', $type))) {
            // Allow extension types for CASE 1.1
            $this->type = $type;

            return $this;
        }

        if (in_array($type, self::BASE_TYPES, true)) {
            $this->type = $type;

            return $this;
        }

        $newType = $this->coerceType($type);
        if (null !== $newType) {
            $this->type = $newType;

            return $this;
        }

        throw new \InvalidArgumentException('Invalid association type passed: '.$type);
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Return the normalized type (like "isChildOf" instead of "Is Child Of").
     */
    public function getNormalizedType(?string $type = null, ?string $caseVersion = null): string
    {
        $returnType = $type ?? $this->type ?? '';

        if (('1.1' !== $caseVersion) && str_starts_with($returnType, 'ext:')) {
            return self::RELATED_TO;
        }

        if (('1.1' !== $caseVersion) && self::IS_TRANSLATION_OF === $returnType) {
            return self::RELATED_TO;
        }

        return $returnType;
    }

    /**
     * Coerce a type string into the correct format for use with setType().
     */
    public function coerceType(?string $type): ?string
    {
        if (array_key_exists($type, self::HUMAN_READABLE_TYPES)) {
            return array_search($type, self::HUMAN_READABLE_TYPES);
        }

        $normalized = preg_replace('/ +/', '', strtolower($type));

        return array_find(self::BASE_TYPES, fn ($value) => strtolower($value) === $normalized);
    }

    public function setOriginLsDoc(?LsDoc $originLsDoc = null): static
    {
        $this->originLsDoc = $originLsDoc;

        if (null !== $originLsDoc) {
            $this->setOriginNodeUri($originLsDoc->getUri());
            $this->setOriginNodeIdentifier($originLsDoc->getIdentifier());
        }

        return $this;
    }

    public function getOriginLsDoc(): ?LsDoc
    {
        return $this->originLsDoc;
    }

    public function setOriginLsItem(?LsItem $originLsItem = null): static
    {
        $this->originLsItem = $originLsItem;

        if (null !== $originLsItem) {
            $this->setOriginNodeUri($originLsItem->getUri());
            $this->setOriginNodeIdentifier($originLsItem->getIdentifier());
        }

        return $this;
    }

    public function getOriginLsItem(): ?LsItem
    {
        return $this->originLsItem;
    }

    public function setDestinationLsDoc(?LsDoc $destinationLsDoc = null): static
    {
        $this->destinationLsDoc = $destinationLsDoc;
        if (null !== $destinationLsDoc) {
            $this->setDestinationNodeUri($destinationLsDoc->getUri());
            $this->setDestinationNodeIdentifier($destinationLsDoc->getIdentifier());
        }

        return $this;
    }

    public function getDestinationLsDoc(): ?LsDoc
    {
        return $this->destinationLsDoc;
    }

    public function setDestinationLsItem(?LsItem $destinationLsItem = null): static
    {
        $this->destinationLsItem = $destinationLsItem;
        if (null !== $destinationLsItem) {
            $this->setDestinationNodeUri($destinationLsItem->getUri());
            $this->setDestinationNodeIdentifier($destinationLsItem->getIdentifier());
        }

        return $this;
    }

    public function getDestinationLsItem(): ?LsItem
    {
        return $this->destinationLsItem;
    }

    public function setLsDocUri(?string $lsDocUri): static
    {
        $this->lsDocUri = $lsDocUri;

        return $this;
    }

    public function getLsDocUri(): ?string
    {
        return $this->lsDocUri;
    }

    public function setLsDoc(LsDoc $lsDoc): static
    {
        $this->lsDoc = $lsDoc;
        $this->setLsDocUri($lsDoc->getUri());
        $this->setLsDocIdentifier($lsDoc->getIdentifier());

        return $this;
    }

    public function getLsDoc(): ?LsDoc
    {
        return $this->lsDoc;
    }

    public function getLsDocIdentifier(): ?string
    {
        return $this->lsDocIdentifier;
    }

    public function setLsDocIdentifier(?string $lsDocIdentifier): static
    {
        $this->lsDocIdentifier = $lsDocIdentifier;

        return $this;
    }

    public function getOriginNodeIdentifier(): ?string
    {
        return $this->originNodeIdentifier;
    }

    public function setOriginNodeIdentifier(?string $originNodeIdentifier): static
    {
        $this->originNodeIdentifier = $originNodeIdentifier;

        return $this;
    }

    public function getDestinationNodeIdentifier(): ?string
    {
        return $this->destinationNodeIdentifier;
    }

    public function setDestinationNodeIdentifier(?string $destinationNodeIdentifier): static
    {
        $this->destinationNodeIdentifier = $destinationNodeIdentifier;

        return $this;
    }

    /**
     * Return true if the association is editable.
     */
    public function canEdit(): bool
    {
        return LsDoc::ADOPTION_STATUS_DEPRECATED !== $this->lsDoc->getAdoptionStatus();
    }

    public function getSequenceNumber(): ?int
    {
        return (int) $this->sequenceNumber;
    }

    public function setSequenceNumber(string|int|null $sequenceNumber): static
    {
        if (null === $sequenceNumber || '' === $sequenceNumber) {
            $this->sequenceNumber = null;

            return $this;
        }

        if (is_string($sequenceNumber) && (1 !== preg_match('/^[0-9]+$/', $sequenceNumber))) {
            throw new \InvalidArgumentException('Sequence number must be an integer');
        }

        $this->sequenceNumber = (int) $sequenceNumber;

        return $this;
    }

    public function getGroup(): ?LsDefAssociationGrouping
    {
        return $this->group;
    }

    public function setGroup(?LsDefAssociationGrouping $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function getSubtype(): ?string
    {
        return $this->subtype;
    }

    public function setSubtype(?string $subtype): static
    {
        $this->subtype = $subtype;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getOriginNodeTargetType(): ?string
    {
        return $this->originNodeTargetType;
    }

    public function setOriginNodeTargetType(?string $originNodeTargetType): LsAssociation
    {
        $this->originNodeTargetType = $originNodeTargetType;

        return $this;
    }

    public function getDestinationNodeTargetType(): ?string
    {
        return $this->destinationNodeTargetType;
    }

    public function setDestinationNodeTargetType(?string $destinationNodeTargetType): LsAssociation
    {
        $this->destinationNodeTargetType = $destinationNodeTargetType;

        return $this;
    }
}
