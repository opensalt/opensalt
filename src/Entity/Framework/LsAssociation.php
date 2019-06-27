<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="ls_association",
 *     indexes={
 *         @ORM\Index(name="dest_id_idx", columns={"destination_node_identifier"}),
 *         @ORM\Index(name="orig_id_idx", columns={"origin_node_identifier"}),
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\Framework\LsAssociationRepository")
 *
 * @Serializer\VirtualProperty(
 *     "cfDocumentUri",
 *     exp="service('App\\Service\\Api1Uris').getLinkUri(object.getLsDoc())",
 *     options={
 *         @Serializer\SerializedName("CFDocumentURI"),
 *         @Serializer\Expose(),
 *         @Serializer\Groups({"LsAssociation"})
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "cfAssociationGroupingUri",
 *     exp="service('App\\Service\\Api1Uris').getLinkUri(object.getGroup())",
 *     options={
 *         @Serializer\SerializedName("CFAssociationGroupingURI"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "originNodeUri",
 *     exp="service('App\\Service\\Api1Uris').getNodeLinkUri('origin', object)",
 *     options={
 *         @Serializer\SerializedName("originNodeURI"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "associationType",
 *     exp="service('App\\Service\\Api1Uris').formatAssociationType(object.getType())",
 *     options={
 *         @Serializer\SerializedName("associationType"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "destinationNodeUri",
 *     exp="service('App\\Service\\Api1Uris').getNodeLinkUri('destination', object)",
 *     options={
 *         @Serializer\SerializedName("destinationNodeURI"),
 *         @Serializer\Expose()
 *     }
 * )
 */
class LsAssociation extends AbstractLsBase implements CaseApiInterface
{
    use AccessAdditionalFieldTrait;

    public const CHILD_OF = 'Is Child Of';

    public const EXACT_MATCH_OF = 'Exact Match Of';
    public const RELATED_TO = 'Is Related To';
    public const PART_OF = 'Is Part Of';
    public const REPLACED_BY = 'Replaced By';
    public const PRECEDES = 'Precedes';
    public const SKILL_LEVEL = 'Has Skill Level';
    public const IS_PEER_OF = 'Is Peer Of';

    public const EXEMPLAR = 'Exemplar';

    public const INVERSE_CHILD_OF = 'Is Parent Of';

    public const INVERSE_EXACT_MATCH_OF = 'Matched From';
    public const INVERSE_RELATED_TO = 'Related From';
    public const INVERSE_PART_OF = 'Has Part';
    public const INVERSE_REPLACED_BY = 'Replaces';
    public const INVERSE_PRECEDES = 'Has Predecessor';
    public const INVERSE_SKILL_LEVEL = 'Skill Level For';
    public const INVERSE_IS_PEER_OF = 'Peer Of';

    public const INVERSE_EXEMPLAR = 'Exemplar For';

    /**
     * @var string
     *
     * @ORM\Column(name="ls_doc_identifier", type="string", length=300, nullable=false)
     *
     * @Assert\Length(max=300)
     *
     * @Serializer\Exclude()
     */
    private $lsDocIdentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="ls_doc_uri", type="string", length=300, nullable=true)
     *
     * @Assert\Length(max=300)
     *
     * @Serializer\Exclude()
     */
    private $lsDocUri;

    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="LsDoc", inversedBy="docAssociations")
     *
     * @Serializer\Exclude()
     */
    private $lsDoc;

    /**
     * @var LsDefAssociationGrouping
     *
     * @ORM\ManyToOne(targetEntity="LsDefAssociationGrouping", fetch="EAGER")
     * @ORM\JoinColumn(name="assoc_group_id", referencedColumnName="id")
     *
     * @Serializer\Exclude()
     */
    private $group;

    /**
     * @var string
     *
     * @ORM\Column(name="group_name", type="string", length=50, nullable=true)
     *
     * @Assert\Length(max=50)
     *
     * @Serializer\Exclude()
     */
    private $groupName;

    /**
     * @var string
     *
     * @ORM\Column(name="group_uri", type="string", length=300, nullable=true)
     *
     * @Assert\Length(max=300)
     *
     * @Serializer\Exclude()
     */
    private $groupUri;

    /**
     * @var string
     *
     * @ORM\Column(name="origin_node_identifier", type="string", length=300, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     *
     * @Serializer\Exclude()
     */
    private $originNodeIdentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="origin_node_uri", type="string", length=300, nullable=true)
     *
     * @Serializer\Exclude()
     */
    private $originNodeUri;

    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="LsDoc", inversedBy="associations", fetch="EAGER")
     * @ORM\JoinColumn(name="origin_lsdoc_id", referencedColumnName="id")
     *
     * @Serializer\Exclude()
     */
    private $originLsDoc;

    /**
     * @var LsItem
     *
     * @ORM\ManyToOne(targetEntity="LsItem", inversedBy="associations", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="origin_lsitem_id", referencedColumnName="id")
     *
     * @Serializer\Exclude()
     */
    private $originLsItem;

    /**
     * @var string
     *
     * @ORM\Column(name="destination_node_identifier", type="string", length=300, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     *
     * @Serializer\Exclude()
     */
    private $destinationNodeIdentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="destination_node_uri", type="string", length=300, nullable=true)
     *
     * @Serializer\Exclude()
     */
    private $destinationNodeUri;

    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="LsDoc", inversedBy="inverseAssociations", fetch="EAGER")
     * @ORM\JoinColumn(name="destination_lsdoc_id", referencedColumnName="id")
     *
     * @Serializer\Exclude()
     */
    private $destinationLsDoc;

    /**
     * @var LsItem
     *
     * @ORM\ManyToOne(targetEntity="LsItem", inversedBy="inverseAssociations", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="destination_lsitem_id", referencedColumnName="id")
     *
     * @Serializer\Exclude()
     */
    private $destinationLsItem;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=50, nullable=false)
     *
     * @Serializer\Exclude()
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="seq", type="bigint", nullable=true)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("sequenceNumber")
     */
    private $sequenceNumber;

    /**
     * @param string|Uuid|null $identifier
     */
    public function __construct($identifier = null)
    {
        parent::__construct($identifier);
    }

    public function __toString(): string
    {
        return $this->getUri();
    }

    /**
     * Get all types collectted by camel case.
     */
    public static function allTypesForImportFromCSV(): array
    {
        return [
            'isPartOf' => static::PART_OF,
            'exemplar' => static::EXEMPLAR,
            'isPeerOf' => static::IS_PEER_OF,
            'precedes' => static::PRECEDES,
            'isRelatedTo' => static::RELATED_TO,
            'replacedBy' => static::REPLACED_BY,
            'hasSkillLevel' => static::SKILL_LEVEL,
        ];
    }

    /**
     * Get an array of all association types available.
     */
    public static function allTypes(): array
    {
        return [
            static::RELATED_TO,
            static::EXACT_MATCH_OF,
            static::PART_OF,
            static::REPLACED_BY,
            static::PRECEDES,
            static::SKILL_LEVEL,
            static::IS_PEER_OF,
            static::EXEMPLAR,

            static::CHILD_OF,
        ];
    }

    /**
     * Get an array of association types that should show in the choice list.
     */
    public static function typeChoiceList(): array
    {
        return [
            static::RELATED_TO,
            static::EXACT_MATCH_OF,
            static::PART_OF,
            static::REPLACED_BY,
            static::PRECEDES,
            static::SKILL_LEVEL,
            static::IS_PEER_OF,
        ];
    }

    public static function inverseName(string $name): ?string
    {
        static $inverses = [];
        if (!count($inverses)) {
            $inverses = [
                static::CHILD_OF => static::INVERSE_CHILD_OF,
                static::EXACT_MATCH_OF => static::INVERSE_EXACT_MATCH_OF,
                static::RELATED_TO => static::INVERSE_RELATED_TO,
                static::PART_OF => static::INVERSE_PART_OF,
                static::REPLACED_BY => static::INVERSE_REPLACED_BY,
                static::PRECEDES => static::INVERSE_PRECEDES,
                static::IS_PEER_OF => static::INVERSE_IS_PEER_OF,
                static::SKILL_LEVEL => static::INVERSE_SKILL_LEVEL,
                static::EXEMPLAR => static::INVERSE_EXEMPLAR,
                static::INVERSE_CHILD_OF => static::CHILD_OF,
                static::INVERSE_EXACT_MATCH_OF => static::EXACT_MATCH_OF,
                static::INVERSE_RELATED_TO => static::RELATED_TO,
                static::INVERSE_PART_OF => static::PART_OF,
                static::INVERSE_REPLACED_BY => static::REPLACED_BY,
                static::INVERSE_PRECEDES => static::PRECEDES,
                static::INVERSE_IS_PEER_OF => static::IS_PEER_OF,
                static::INVERSE_SKILL_LEVEL => static::SKILL_LEVEL,
                static::INVERSE_EXEMPLAR => static::EXEMPLAR,
            ];
        }

        if (array_key_exists($name, $inverses)) {
            return $inverses[$name];
        }

        return null;
    }

    /**
     * Return true if this is an LsAssociation.
     */
    public function isLsAssociation(): bool
    {
        return true;
    }

    /**
     * Set the Origination of the association.
     *
     * @param string|IdentifiableInterface $origin
     *
     * @throws \UnexpectedValueException
     */
    public function setOrigin($origin, ?string $identifier = null): self
    {
        if (is_string($origin)) {
            $this->setOriginNodeUri($origin);
            $this->setOriginNodeIdentifier($identifier ?? $origin);
        } elseif ($origin instanceof IdentifiableInterface) {
            if ($origin instanceof LsDoc) {
                $this->setOriginLsDoc($origin);
            } elseif ($origin instanceof LsItem) {
                $this->setOriginLsItem($origin);
            }
            $this->setOriginNodeUri($origin->getUri());
            $this->setOriginNodeIdentifier($identifier ?? $origin->getIdentifier());
        } else {
            throw new \UnexpectedValueException('The value must be a URI, an LsDoc, or an LsItem');
        }

        return $this;
    }

    /**
     * Get the Origination of the association.
     *
     * @return null|string|LsDoc|LsItem
     */
    public function getOrigin()
    {
        if ($this->getOriginLsDoc()) {
            return $this->getOriginLsDoc();
        }

        if ($this->getOriginLsItem()) {
            return $this->getOriginLsItem();
        }

        if ($this->getOriginNodeUri()) {
            return $this->getOriginNodeUri();
        }

        return null;
    }

    public function setOriginNodeUri(string $originNodeUri): self
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
     * @param string|IdentifiableInterface $destination
     *
     * @throws \UnexpectedValueException
     */
    public function setDestination($destination, ?string $identifier = null): self
    {
        if (is_string($destination)) {
            $this->setDestinationNodeUri($destination);
            $this->setDestinationNodeIdentifier($identifier ?? $destination);
        } elseif ($destination instanceof IdentifiableInterface) {
            if ($destination instanceof LsDoc) {
                $this->setDestinationLsDoc($destination);
            } elseif ($destination instanceof LsItem) {
                $this->setDestinationLsItem($destination);
            }
            $this->setDestinationNodeUri($destination->getUri());
            $this->setDestinationNodeIdentifier($identifier ?? $destination->getIdentifier());
        } else {
            throw new \UnexpectedValueException('The value must be a URI, an LsDoc, or an LsItem');
        }

        return $this;
    }

    /**
     * Get the Destination of the association.
     *
     * @return null|string|LsDoc|LsItem
     */
    public function getDestination()
    {
        if ($this->getDestinationLsDoc()) {
            return $this->getDestinationLsDoc();
        }

        if ($this->getDestinationLsItem()) {
            return $this->getDestinationLsItem();
        }

        if ($this->getDestinationNodeUri()) {
            return $this->getDestinationNodeUri();
        }

        return null;
    }

    public function setDestinationNodeUri(string $destinationNodeUri): self
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
        if (0 !== strncmp($this->destinationNodeUri, 'data:text/x-', 12)) {
            // Not a known data URI format, return the entire uri as the value
            return ['value' => $this->destinationNodeUri];
        }

        $uri = substr($this->destinationNodeUri, 12);
        [$dataString, $encodedValue] = array_pad(explode(',', $uri, 2), 2, null);

        [$textType, $metadataString] = array_pad(explode(';', $dataString, 2), 2, null);

        $metadata = ['textType' => $textType];
        foreach (explode(';', $metadataString) as $param) {
            [$name, $value] = array_pad(explode('=', $param, 2), 2, null);
            if (null !== $name && '' !== $name) {
                $metadata[$name] = $value ?? true;
            }
        }

        if ($metadata['base64'] ?? false) {
            $metadata['value'] = base64_decode($encodedValue);
        } else {
            $metadata['value'] = rawurldecode($encodedValue);
        }

        return $metadata;
    }

    public function setType(?string $type): self
    {
        if (in_array($type, self::allTypes(), true)) {
            $this->type = $type;

            return $this;
        }

        $newType = $this->coerceType($type);
        if (null !== $newType) {
            $this->type = $type;

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
    public function getNormalizedType(): string
    {
        return lcfirst(str_replace(' ', '', $this->type ?? ''));
    }

    /**
     * Coerce a type string into the correct format for use with setType().
     */
    public function coerceType(?string $type): ?string
    {
        $allTypes = self::allTypes();
        $testNewType = preg_replace('/ +/', '', strtolower($type));
        foreach ($allTypes as $allowedType) {
            $testAllowedType = preg_replace('/ +/', '', strtolower($allowedType));
            if ($testNewType === $testAllowedType) {
                return $allowedType;
            }
        }

        return null;
    }

    public function setOriginLsDoc(?LsDoc $originLsDoc = null): self
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

    public function setOriginLsItem(?LsItem $originLsItem = null): self
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

    public function setDestinationLsDoc(?LsDoc $destinationLsDoc = null): self
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

    public function setDestinationLsItem(?LsItem $destinationLsItem = null): self
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

    public function setLsDocUri(?string $lsDocUri): self
    {
        $this->lsDocUri = $lsDocUri;

        return $this;
    }

    public function getLsDocUri(): ?string
    {
        return $this->lsDocUri;
    }

    public function setLsDoc(LsDoc $lsDoc): self
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

    public function setGroupName(?string $groupName): self
    {
        $this->groupName = $groupName;

        return $this;
    }

    public function getGroupName(): ?string
    {
        if ($this->groupName) {
            return $this->groupName;
        }

        if ($this->group) {
            return $this->group->getTitle();
        }

        return null;
    }

    public function getLsDocIdentifier(): ?string
    {
        return $this->lsDocIdentifier;
    }

    public function setLsDocIdentifier(?string $lsDocIdentifier): self
    {
        $this->lsDocIdentifier = $lsDocIdentifier;

        return $this;
    }

    public function getOriginNodeIdentifier(): ?string
    {
        return $this->originNodeIdentifier;
    }

    public function setOriginNodeIdentifier(?string $originNodeIdentifier): self
    {
        $this->originNodeIdentifier = $originNodeIdentifier;

        return $this;
    }

    public function getDestinationNodeIdentifier(): ?string
    {
        return $this->destinationNodeIdentifier;
    }

    public function setDestinationNodeIdentifier(?string $destinationNodeIdentifier): self
    {
        $this->destinationNodeIdentifier = $destinationNodeIdentifier;

        return $this;
    }

    public function getGroupUri(): ?string
    {
        if ($this->groupUri) {
            return $this->groupUri;
        }

        if ($this->group) {
            return $this->group->getUri();
        }

        return null;
    }

    public function setGroupUri(?string $groupUri): self
    {
        $this->groupUri = $groupUri;

        return $this;
    }

    /**
     * Return true if the association is editable.
     */
    public function canEdit(): bool
    {
        return !(LsDoc::ADOPTION_STATUS_DEPRECATED === $this->lsDoc->getAdoptionStatus());
    }

    public function getSequenceNumber(): ?int
    {
        return $this->sequenceNumber;
    }

    public function setSequenceNumber(?int $sequenceNumber): self
    {
        $this->sequenceNumber = $sequenceNumber;

        return $this;
    }

    public function getGroup(): ?LsDefAssociationGrouping
    {
        return $this->group;
    }

    public function setGroup(?LsDefAssociationGrouping $group): self
    {
        $this->group = $group;

        return $this;
    }
}
