<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * LsAssociation
 *
 * @ORM\Table(name="ls_association")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\LsAssociationRepository")
 *
 * @Serializer\VirtualProperty(
 *     "uri",
 *     exp="service('salt.api.v1p0.utils').getApiUrl(object)",
 *     options={
 *         @Serializer\SerializedName("uri"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "cfDocumentUri",
 *     exp="service('salt.api.v1p0.utils').getLinkUri(object.getLsDoc())",
 *     options={
 *         @Serializer\SerializedName("CFDocumentURI"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "cfAssociationGroupingUri",
 *     exp="service('salt.api.v1p0.utils').getLinkUri(object.getGroup())",
 *     options={
 *         @Serializer\SerializedName("CFAssociationGroupingURI"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "originNodeUri",
 *     exp="service('salt.api.v1p0.utils').getNodeLinkUri('origin', object)",
 *     options={
 *         @Serializer\SerializedName("originNodeURI"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "associationType",
 *     exp="service('salt.api.v1p0.utils').formatAssociationType(object.getType())",
 *     options={
 *         @Serializer\SerializedName("associationType"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "destinationNodeUri",
 *     exp="service('salt.api.v1p0.utils').getNodeLinkUri('destination', object)",
 *     options={
 *         @Serializer\SerializedName("destinationNodeURI"),
 *         @Serializer\Expose()
 *     }
 * )
 */
class LsAssociation extends AbstractLsBase implements CaseApiInterface
{
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
    public const INVERSE_IS_PEER_OF = 'Is Peer Of';

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
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc", inversedBy="docAssociations")
     *
     * @Serializer\Exclude()
     */
    private $lsDoc;

    /**
     * @var LsDefAssociationGrouping
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDefAssociationGrouping", fetch="EAGER")
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
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc", inversedBy="associations", fetch="EAGER")
     * @ORM\JoinColumn(name="origin_lsdoc_id", referencedColumnName="id")
     *
     * @Serializer\Exclude()
     */
    private $originLsDoc;

    /**
     * @var LsItem
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsItem", inversedBy="associations", fetch="EAGER", cascade={"persist"})
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
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc", inversedBy="inverseAssociations", fetch="EAGER")
     * @ORM\JoinColumn(name="destination_lsdoc_id", referencedColumnName="id")
     *
     * @Serializer\Exclude()
     */
    private $destinationLsDoc;

    /**
     * @var LsItem
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsItem", inversedBy="inverseAssociations", fetch="EAGER", cascade={"persist"})
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
     * Constructor
     *
     * @param string|Uuid|null $identifier
     */
    public function __construct($identifier = null)
    {
        parent::__construct($identifier);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUri();
    }

    /**
     * Get all types collectted by camel case.
     *
     * @return array
     */
    public static function allTypesForImportFromCSV(){
        return [
            'isPartOf' =>                     static::PART_OF,
            'exemplar' =>                     static::EXEMPLAR,
            'isPeerOf' =>                     static::IS_PEER_OF,
            'precedes' =>                     static::PRECEDES,
            'isRelatedTo' =>                  static::RELATED_TO,
            'replacedBy' =>                   static::REPLACED_BY,
            'hasSkillLevel' =>                static::SKILL_LEVEL,
        ];
    }

    /**
     * Get an array of all association types available
     *
     * @return array
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
     * Get an array of association types that should show in the choice list
     *
     * @return array
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

    /**
     * @param string $name
     *
     * @return string|null
     */
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
     * Return true if this is an LsAssociation
     *
     * @return bool
     */
    public function isLsAssociation(): bool
    {
        return true;
    }

    /**
     * Set the Origination of the association
     *
     * @param string|IdentifiableInterface $origin
     * @param string|null $identifier
     *
     * @return LsAssociation
     *
     * @throws \UnexpectedValueException
     */
    public function setOrigin($origin, ?string $identifier = null): LsAssociation
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
     * Get the Origination of the association
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

    /**
     * Set originNodeUri
     *
     * @param string $originNodeUri
     *
     * @return LsAssociation
     */
    public function setOriginNodeUri(string $originNodeUri): LsAssociation
    {
        $this->originNodeUri = $originNodeUri;

        return $this;
    }

    /**
     * Get originNodeUri
     *
     * @return string
     */
    public function getOriginNodeUri()
    {
        return $this->originNodeUri;
    }

    /**
     * Set the Destination of the association
     *
     * @param string|IdentifiableInterface $destination
     * @param string|null $identifier
     *
     * @return LsAssociation
     *
     * @throws \UnexpectedValueException
     */
    public function setDestination($destination, ?string $identifier = null): LsAssociation
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
     * Get the Destination of the association
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

    /**
     * Set destinationNodeUri
     *
     * @param string $destinationNodeUri
     *
     * @return LsAssociation
     */
    public function setDestinationNodeUri(string $destinationNodeUri): LsAssociation
    {
        $this->destinationNodeUri = $destinationNodeUri;

        return $this;
    }

    /**
     * Get destinationNodeUri
     *
     * @return string
     */
    public function getDestinationNodeUri()
    {
        return $this->destinationNodeUri;
    }

    /**
     * Get HumanCodingScheme from DestinationNodeUri
     *
     * @return string
     */
    public function getHumanCodingSchemeFromDestinationNodeUri()
    {
        return $this->splitDestinationDataUri()['value'];
    }

    /**
     * Get an array with the information from a data URI
     *
     * @return array
     */
    public function splitDestinationDataUri()
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

    /**
     * Set type
     *
     * @param string $type
     *
     * @return LsAssociation
     */
    public function setType($type): LsAssociation
    {
        $this->type = $type;

        return $this;
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
     * Return the normalized type (like "isChildOf" instead of "Is Child Of")
     */
    public function getNormalizedType(): string
    {
        return lcfirst(str_replace(' ', '', $this->type));
    }

    /**
     * Set originLsDoc
     *
     * @param \CftfBundle\Entity\LsDoc $originLsDoc
     *
     * @return LsAssociation
     */
    public function setOriginLsDoc(?LsDoc $originLsDoc = null): LsAssociation
    {
        $this->originLsDoc = $originLsDoc;

        if (null !== $originLsDoc) {
            $this->setOriginNodeUri($originLsDoc->getUri());
            $this->setOriginNodeIdentifier($originLsDoc->getIdentifier());
        }

        return $this;
    }

    /**
     * Get originLsDoc
     *
     * @return \CftfBundle\Entity\LsDoc
     */
    public function getOriginLsDoc(): ?LsDoc
    {
        return $this->originLsDoc;
    }

    /**
     * Set originLsItem
     *
     * @param \CftfBundle\Entity\LsItem $originLsItem
     *
     * @return LsAssociation
     */
    public function setOriginLsItem(?LsItem $originLsItem = null): LsAssociation
    {
        $this->originLsItem = $originLsItem;

        if (null !== $originLsItem) {
            $this->setOriginNodeUri($originLsItem->getUri());
            $this->setOriginNodeIdentifier($originLsItem->getIdentifier());
        }

        return $this;
    }

    /**
     * Get originLsItem
     *
     * @return \CftfBundle\Entity\LsItem
     */
    public function getOriginLsItem(): ?LsItem
    {
        return $this->originLsItem;
    }

    /**
     * Set destinationLsDoc
     *
     * @param \CftfBundle\Entity\LsDoc $destinationLsDoc
     *
     * @return LsAssociation
     */
    public function setDestinationLsDoc(?LsDoc $destinationLsDoc = null): LsAssociation
    {
        $this->destinationLsDoc = $destinationLsDoc;
        if (null !== $destinationLsDoc) {
            $this->setDestinationNodeUri($destinationLsDoc->getUri());
            $this->setDestinationNodeIdentifier($destinationLsDoc->getIdentifier());
        }

        return $this;
    }

    /**
     * Get destinationLsDoc
     *
     * @return \CftfBundle\Entity\LsDoc
     */
    public function getDestinationLsDoc(): ?LsDoc
    {
        return $this->destinationLsDoc;
    }

    /**
     * Set destinationLsItem
     *
     * @param \CftfBundle\Entity\LsItem $destinationLsItem
     *
     * @return LsAssociation
     */
    public function setDestinationLsItem(?LsItem $destinationLsItem = null): LsAssociation
    {
        $this->destinationLsItem = $destinationLsItem;
        if (null !== $destinationLsItem) {
            $this->setDestinationNodeUri($destinationLsItem->getUri());
            $this->setDestinationNodeIdentifier($destinationLsItem->getIdentifier());
        }

        return $this;
    }

    /**
     * Get destinationLsItem
     *
     * @return \CftfBundle\Entity\LsItem
     */
    public function getDestinationLsItem(): ?LsItem
    {
        return $this->destinationLsItem;
    }

    /**
     * Set lsDocUri
     *
     * @param string $lsDocUri
     *
     * @return LsAssociation
     */
    public function setLsDocUri($lsDocUri): LsAssociation
    {
        $this->lsDocUri = $lsDocUri;

        return $this;
    }

    /**
     * Get lsDocUri
     *
     * @return string
     */
    public function getLsDocUri()
    {
        return $this->lsDocUri;
    }

    /**
     * Set lsDoc
     *
     * @param LsDoc $lsDoc
     *
     * @return LsAssociation
     */
    public function setLsDoc(LsDoc $lsDoc): LsAssociation
    {
        $this->lsDoc = $lsDoc;
        $this->setLsDocUri($lsDoc->getUri());
        $this->setLsDocIdentifier($lsDoc->getIdentifier());

        return $this;
    }

    /**
     * Get lsDoc
     *
     * @return \CftfBundle\Entity\LsDoc
     */
    public function getLsDoc(): ?LsDoc
    {
        return $this->lsDoc;
    }

    /**
     * @param string $groupName
     *
     * @return LsAssociation
     */
    public function setGroupName($groupName): LsAssociation
    {
        $this->groupName = $groupName;

        return $this;
    }

    /**
     * @return string|null
     */
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

    /**
     * @return string
     */
    public function getLsDocIdentifier()
    {
        return $this->lsDocIdentifier;
    }

    /**
     * @param string $lsDocIdentifier
     *
     * @return LsAssociation
     */
    public function setLsDocIdentifier($lsDocIdentifier): LsAssociation
    {
        $this->lsDocIdentifier = $lsDocIdentifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginNodeIdentifier()
    {
        return $this->originNodeIdentifier;
    }

    /**
     * @param string $originNodeIdentifier
     *
     * @return LsAssociation
     */
    public function setOriginNodeIdentifier($originNodeIdentifier): LsAssociation
    {
        $this->originNodeIdentifier = $originNodeIdentifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getDestinationNodeIdentifier()
    {
        return $this->destinationNodeIdentifier;
    }

    /**
     * @param string $destinationNodeIdentifier
     *
     * @return LsAssociation
     */
    public function setDestinationNodeIdentifier($destinationNodeIdentifier): LsAssociation
    {
        $this->destinationNodeIdentifier = $destinationNodeIdentifier;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getGroupUri()
    {
        if ($this->groupUri) {
            return $this->groupUri;
        }

        if ($this->group) {
            return $this->group->getUri();
        }

        return null;
    }

    /**
     * @param string $groupUri
     *
     * @return LsAssociation
     */
    public function setGroupUri($groupUri): LsAssociation
    {
        $this->groupUri = $groupUri;

        return $this;
    }

    /**
     * Determine if the LsAssociation is editable
     *
     * @return bool
     */
    public function canEdit(): bool
    {
        return !(LsDoc::ADOPTION_STATUS_DEPRECATED === $this->lsDoc->getAdoptionStatus());
    }

    /**
     * @return int|null
     */
    public function getSequenceNumber(): ?int
    {
        return $this->sequenceNumber;
    }

    /**
     * @param int|null $sequenceNumber
     *
     * @return LsAssociation
     */
    public function setSequenceNumber(?int $sequenceNumber): LsAssociation
    {
        $this->sequenceNumber = $sequenceNumber;

        return $this;
    }

    /**
     * @return LsDefAssociationGrouping
     */
    public function getGroup(): ?LsDefAssociationGrouping
    {
        return $this->group;
    }

    /**
     * @param LsDefAssociationGrouping|null $group
     *
     * @return LsAssociation
     */
    public function setGroup(?LsDefAssociationGrouping $group): LsAssociation
    {
        $this->group = $group;

        return $this;
    }
}
