<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * LsAssociation
 *
 * @ORM\Table(name="ls_association")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\LsAssociationRepository")
 */
class LsAssociation
{
    const CHILD_OF = 'Is Child Of';

    const EXACT_MATCH_OF = 'Exact Match Of';
    const RELATED_TO = 'Is Related To';
    const PART_OF = 'Is Part Of';
    const REPLACED_BY = 'Replaced By';
    const PRECEDES = 'Precedes';
    const PREREQUISITE = 'Prerequisite';
    const SKILL_LEVEL = 'Has Skill Level';

    const EXEMPLAR = 'Exemplar';


    const INVERSE_CHILD_OF = 'Is Parent Of';

    const INVERSE_EXACT_MATCH_OF = 'Matched From';
    const INVERSE_RELATED_TO = 'Related From';
    const INVERSE_PART_OF = 'Has Part';
    const INVERSE_REPLACED_BY = 'Replaces';
    const INVERSE_PRECEDES = 'Has Predecesor';
    const INVERSE_PREREQUISITE = 'Has Prerequisite';
    const INVERSE_SKILL_LEVEL = 'Skill Level For';

    const INVERSE_EXEMPLAR = 'Exemplar For';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="ls_doc_identifier", type="string", length=300, nullable=false)
     *
     * @Assert\Length(max=300)
     */
    private $lsDocIdentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="ls_doc_uri", type="string", length=300, nullable=true)
     *
     * @Assert\Length(max=300)
     */
    private $lsDocUri;

    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc", inversedBy="docAssociations")
     */
    private $lsDoc;

    /**
     * @var string
     *
     * @ORM\Column(name="identifier", type="string", length=300, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     */
    private $identifier;

    /**
     * @var string
     *
     * @ORM\Column(name="uri", type="string", length=300, nullable=true)
     */
    private $uri;

    /**
     * @var string
     *
     * @ORM\Column(name="group_name", type="string", length=50, nullable=true)
     *
     * @Assert\Length(max=50)
     */
    private $groupName;

    /**
     * @var string
     *
     * @ORM\Column(name="group_uri", type="string", length=300, nullable=true)
     *
     * @Assert\Length(max=300)
     */
    private $groupUri;

    /**
     * @var string
     *
     * @ORM\Column(name="origin_node_identifier", type="string", length=300, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     */
    private $originNodeIdentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="origin_node_uri", type="string", length=300, nullable=true)
     */
    private $originNodeUri;

    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc", inversedBy="associations", fetch="EAGER")
     * @ORM\JoinColumn(name="origin_lsdoc_id", referencedColumnName="id")
     */
    private $originLsDoc;

    /**
     * @var LsItem
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsItem", inversedBy="associations", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="origin_lsitem_id", referencedColumnName="id")
     */
    private $originLsItem;

    /**
     * @var string
     *
     * @ORM\Column(name="destination_node_identifier", type="string", length=300, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     */
    private $destinationNodeIdentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="destination_node_uri", type="string", length=300, nullable=true)
     */
    private $destinationNodeUri;

    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc", inversedBy="inverseAssociations", fetch="EAGER")
     * @ORM\JoinColumn(name="destination_lsdoc_id", referencedColumnName="id")
     */
    private $destinationLsDoc;

    /**
     * @var LsItem
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsItem", inversedBy="inverseAssociations", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="destination_lsitem_id", referencedColumnName="id")
     */
    private $destinationLsItem;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=50, nullable=false)
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", columnDefinition="DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;



    public function __construct()
    {
        $this->identifier = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->uri = 'local:'.$this->identifier;
    }

    public function __toString()
    {
        return $this->uri;
    }

    public static function allTypes()
    {
        return [
            static::EXACT_MATCH_OF,
            static::RELATED_TO,
            static::PART_OF,
            static::REPLACED_BY,
            static::PRECEDES,
            static::PREREQUISITE,
            static::SKILL_LEVEL,

            static::CHILD_OF,
        ];
    }

    public static function typeChoiceList()
    {
        return [
            static::RELATED_TO,
            static::EXACT_MATCH_OF,
            static::PART_OF,
            static::REPLACED_BY,
            static::PRECEDES,
            static::PREREQUISITE,
            static::SKILL_LEVEL,
        ];
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public static function inverseName($name)
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
                static::PREREQUISITE => static::INVERSE_PREREQUISITE,
                static::SKILL_LEVEL => static::INVERSE_SKILL_LEVEL,
                static::EXEMPLAR => static::INVERSE_EXEMPLAR,
                static::INVERSE_CHILD_OF => static::CHILD_OF,
                static::INVERSE_EXACT_MATCH_OF => static::EXACT_MATCH_OF,
                static::INVERSE_RELATED_TO => static::RELATED_TO,
                static::INVERSE_PART_OF => static::PART_OF,
                static::INVERSE_REPLACED_BY => static::REPLACED_BY,
                static::INVERSE_PRECEDES => static::PRECEDES,
                static::INVERSE_PREREQUISITE => static::PREREQUISITE,
                static::INVERSE_SKILL_LEVEL => static::SKILL_LEVEL,
                static::INVERSE_EXEMPLAR => static::EXEMPLAR,
            ];
        }

        if (array_key_exists($name, $inverses)) {
            return $inverses[$name];
        }

        return null;
    }

    public function isLsAssociation()
    {
        return true;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set uri
     *
     * @param string $uri
     *
     * @return LsAssociation
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Get uri
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set the Origination of the association
     *
     * @param string|LsDoc|LsItem $origin
     *
     * @return $this
     */
    public function setOrigin($origin)
    {
        if (is_string($origin)) {
            $this->setOriginNodeUri($origin);
            $this->setOriginNodeIdentifier($origin);
        } elseif ($origin instanceof LsDoc) {
            $this->setOriginLsDoc($origin);
            $this->setOriginNodeUri($origin->getUri());
            $this->setOriginNodeIdentifier($origin->getIdentifier());
        } elseif ($origin instanceof LsItem) {
            $this->setOriginLsItem($origin);
            $this->setOriginNodeUri($origin->getUri());
            $this->setOriginNodeIdentifier($origin->getIdentifier());
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
        } elseif ($this->getOriginLsItem()) {
            return $this->getOriginLsItem();
        } elseif ($this->getOriginNodeUri()) {
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
    public function setOriginNodeUri($originNodeUri)
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
     * @param string|LsDoc|LsItem $origin
     *
     * @return $this
     */
    public function setDestination($origin)
    {
        if (is_string($origin)) {
            $this->setDestinationNodeUri($origin);
            $this->setDestinationNodeIdentifier($origin);
        } elseif ($origin instanceof LsDoc) {
            $this->setDestinationLsDoc($origin);
            $this->setDestinationNodeUri($origin->getUri());
            $this->setDestinationNodeIdentifier($origin->getIdentifier());
        } elseif ($origin instanceof LsItem) {
            $this->setDestinationLsItem($origin);
            $this->setDestinationNodeUri($origin->getUri());
            $this->setDestinationNodeIdentifier($origin->getIdentifier());
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
        } elseif ($this->getDestinationLsItem()) {
            return $this->getDestinationLsItem();
        } elseif ($this->getDestinationNodeUri()) {
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
    public function setDestinationNodeUri($destinationNodeUri)
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
     * Set type
     *
     * @param string $type
     *
     * @return LsAssociation
     */
    public function setType($type)
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return LsAssociation
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set originLsDoc
     *
     * @param \CftfBundle\Entity\LsDoc $originLsDoc
     *
     * @return LsAssociation
     */
    public function setOriginLsDoc(\CftfBundle\Entity\LsDoc $originLsDoc = null)
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
    public function getOriginLsDoc()
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
    public function setOriginLsItem(\CftfBundle\Entity\LsItem $originLsItem = null)
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
    public function getOriginLsItem()
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
    public function setDestinationLsDoc(\CftfBundle\Entity\LsDoc $destinationLsDoc = null)
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
    public function getDestinationLsDoc()
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
    public function setDestinationLsItem(\CftfBundle\Entity\LsItem $destinationLsItem = null)
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
    public function getDestinationLsItem()
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
    public function setLsDocUri($lsDocUri)
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
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     *
     * @return LsAssociation
     */
    public function setLsDoc(\CftfBundle\Entity\LsDoc $lsDoc = null)
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
    public function getLsDoc()
    {
        return $this->lsDoc;
    }

    /**
     * @param string $groupName
     *
     * @return LsAssociation
     */
    public function setGroupName($groupName) {
        $this->groupName = $groupName;
        return $this;
    }

    /**
     * @return string
     */
    public function getGroupName() {
        return $this->groupName;
    }

    /**
     * @return string
     */
    public function getLsDocIdentifier() {
        return $this->lsDocIdentifier;
    }

    /**
     * @param string $lsDocIdentifier
     *
     * @return LsAssociation
     */
    public function setLsDocIdentifier($lsDocIdentifier) {
        $this->lsDocIdentifier = $lsDocIdentifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return LsAssociation
     */
    public function setIdentifier($identifier) {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getOriginNodeIdentifier() {
        return $this->originNodeIdentifier;
    }

    /**
     * @param string $originNodeIdentifier
     *
     * @return LsAssociation
     */
    public function setOriginNodeIdentifier($originNodeIdentifier) {
        $this->originNodeIdentifier = $originNodeIdentifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getDestinationNodeIdentifier() {
        return $this->destinationNodeIdentifier;
    }

    /**
     * @param string $destinationNodeIdentifier
     *
     * @return LsAssociation
     */
    public function setDestinationNodeIdentifier($destinationNodeIdentifier) {
        $this->destinationNodeIdentifier = $destinationNodeIdentifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getGroupUri() {
        return $this->groupUri;
    }

    /**
     * @param string $groupUri
     *
     * @return LsAssociation
     */
    public function setGroupUri($groupUri) {
        $this->groupUri = $groupUri;
        return $this;
    }

    /**
     * Determine if the LsAssociation is editable
     *
     * @return bool
     */
    public function canEdit() {
        return !(LsDoc::ADOPTION_STATUS_DEPRECATED === $this->lsDoc->getAdoptionStatus());
    }
}
