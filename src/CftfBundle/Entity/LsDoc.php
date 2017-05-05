<?php

namespace CftfBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Salt\UserBundle\Entity\Organization;
use Salt\UserBundle\Entity\User;
use Salt\UserBundle\Entity\UserDocAcl;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Util\Compare;

/**
 * LsDoc
 *
 * @ORM\Table(name="ls_doc")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\LsDocRepository")
 * @UniqueEntity("uri")
 *
 * @Serializer\VirtualProperty(
 *     "uri",
 *     exp="service('salt.api.v1p1.utils').getApiUrl(object)",
 *     options={
 *         @Serializer\SerializedName("uri"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "cfPackageUri",
 *     exp="service('salt.api.v1p1.utils').getApiUrl(object, 'api_v1p1_cfpackage')",
 *     options={
 *         @Serializer\SerializedName("CFPackageURI"),
 *         @Serializer\Expose()
 *     }
 * )
 */
class LsDoc implements CaseApiInterface
{
    const ADOPTION_STATUS_PRIVATE_DRAFT = 'Private Draft';
    const ADOPTION_STATUS_DRAFT = 'Draft';
    const ADOPTION_STATUS_ADOPTED = 'Adopted';
    const ADOPTION_STATUS_DEPRECATED = 'Deprecated';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @Serializer\Exclude()
     */
    private $id;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Salt\UserBundle\Entity\Organization", inversedBy="frameworks")
     * @ORM\JoinColumn(name="org_id", referencedColumnName="id", nullable=true)
     *
     * @Serializer\Exclude()
     */
    protected $org;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Salt\UserBundle\Entity\User", inversedBy="frameworks")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     *
     * @Serializer\Exclude()
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="uri", type="string", length=300, nullable=true, unique=true)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     *
     * @Serializer\Exclude()
     */
    private $uri;

    /**
     * @var string
     *
     * @ORM\Column(name="identifier", type="string", length=300, nullable=false, unique=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     *
     * @Serializer\Expose()
     */
    private $identifier;

    /**
     * @var string
     *
     * @ORM\Column(name="official_uri", type="string", length=300, nullable=true)
     *
     * @Assert\Length(max=300)
     * @Assert\Url()
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("officialSourceURL")
     */
    private $officialUri;

    /**
     * @var string
     *
     * @ORM\Column(name="creator", type="string", length=300, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     *
     * @Serializer\Expose()
     */
    private $creator;

    /**
     * @var string
     *
     * @ORM\Column(name="publisher", type="string", length=50, nullable=true)
     *
     * @Assert\Length(max=50)
     *
     * @Serializer\Expose()
     */
    private $publisher;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=120, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=120)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="url_name", type="string", length=255, nullable=true, unique=true)
     *
     * @Assert\Length(max=10)
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/^\d+$/",
     *     match=false,
     *     message="The URL Name cannot be a number."
     * )
     * @Assert\Regex(
     *     pattern="/^[a-zA-Z0-9.-]+$/",
     *     message="The URL Name can only use alpha-numeric characters plus a period (.) or dash (-)."
     * )
     */
    private $urlName;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=50, nullable=true)
     *
     * @Assert\Length(max=50)
     *
     * @Serializer\Expose()
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=300, nullable=true)
     *
     * @Assert\Length(max=300)
     *
     * @Serializer\Expose()
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=50, nullable=true)
     *
     * @Assert\Length(max=50)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="subject_uri", type="string", length=300, nullable=true)
     *
     * @Assert\Url()
     * @Assert\Length(max=300)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("subjectURI")
     */
    private $subjectUri;

    /**
     * @var LsDefSubject[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="CftfBundle\Entity\LsDefSubject")
     * @ORM\JoinTable(name="ls_doc_subject",
     *      joinColumns={@ORM\JoinColumn(name="ls_doc_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="subject_id", referencedColumnName="id")}
     * )
     *
     * @Serializer\Exclude()
     * @Serializer\SerializedName("subject")
     * @Serializer\Type("array<string>")
     */
    private $subjects;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=10, nullable=true)
     *
     * @Assert\Length(max=10)
     *
     * @Serializer\Expose(if="object.getLanguage() != ''")
     */
    private $language;

    /**
     * @var string
     *
     * @ORM\Column(name="adoption_status", type="string", length=50, nullable=true)
     *
     * @Assert\Length(max=50)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("adoptionStatus")
     */
    private $adoptionStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="status_start", type="date", nullable=true)
     *
     * @Assert\Date()
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("statusStartDate")
     */
    private $statusStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="status_end", type="date", nullable=true)
     *
     * @Assert\Date()
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("statusEndDate")
     */
    private $statusEnd;

    /**
     * @var string
     *
     * @ORM\Column(name="note", type="text", nullable=true)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("notes")
     */
    private $note;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", columnDefinition="DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL")
     * @Gedmo\Timestampable(on="update")
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("lastChangeDateTime")
     */
    private $updatedAt;

    /**
     * @var Collection|LsItem[]
     *
     * @ORM\OneToMany(targetEntity="CftfBundle\Entity\LsItem", mappedBy="lsDoc", indexBy="id", fetch="EXTRA_LAZY")
     *
     * @Serializer\Exclude()
     */
    private $lsItems;

    /**
     * @var Collection|LsAssociation[]
     *
     * @ORM\OneToMany(targetEntity="CftfBundle\Entity\LsAssociation", mappedBy="lsDoc", indexBy="id", fetch="EXTRA_LAZY")
     *
     * @Serializer\Exclude()
     */
    private $docAssociations;

    /**
     * @var Collection|LsAssociation[]
     *
     * @ORM\OneToMany(targetEntity="CftfBundle\Entity\LsAssociation", mappedBy="originLsDoc", indexBy="id", cascade={"persist"})
     *
     * @Serializer\Exclude()
     */
    private $associations;

    /**
     * @var Collection|LsAssociation[]
     *
     * @ORM\OneToMany(targetEntity="CftfBundle\Entity\LsAssociation", mappedBy="destinationLsDoc", indexBy="id", cascade={"persist"})
     *
     * @Serializer\Exclude()
     */
    private $inverseAssociations;

    /**
     * @var LsDocAttribute[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CftfBundle\Entity\LsDocAttribute", mappedBy="lsDoc", cascade={"ALL"}, indexBy="attribute", orphanRemoval=true)
     *
     * @Serializer\Exclude()
     */
    private $attributes;

    /**
     * @var UserDocAcl[]|Collection
     * @ORM\OneToMany(targetEntity="Salt\UserBundle\Entity\UserDocAcl", mappedBy="lsDoc", indexBy="user", fetch="EXTRA_LAZY")
     *
     * @Serializer\Exclude()
     */
    protected $docAcls;

    /**
     * @var LsDefAssociationGrouping[]|Collection
     * @ORM\OneToMany(targetEntity="LsDefAssociationGrouping", mappedBy="lsDoc", indexBy="id", fetch="EXTRA_LAZY")
     *
     * @Serializer\Exclude()
     */
    protected $associationGroupings;

    /**
     * @var string
     *
     * @Serializer\Exclude()
     */
    protected $ownedBy;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->identifier = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->uri = 'local:'.$this->identifier;
        $this->lsItems = new ArrayCollection();
        $this->docAssociations = new ArrayCollection();
        $this->associations = new ArrayCollection();
        $this->inverseAssociations = new ArrayCollection();
        $this->attributes = new ArrayCollection();
        $this->subjects = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->uri;
    }

    /**
     * @return bool
     */
    public function isLsDoc()
    {
        return true;
    }

    /**
     * Get the list of Adoption Statuses
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            static::ADOPTION_STATUS_PRIVATE_DRAFT,
            static::ADOPTION_STATUS_DRAFT,
            static::ADOPTION_STATUS_ADOPTED,
            static::ADOPTION_STATUS_DEPRECATED,
        ];
    }

    /**
     * Get the list of Adoption Statuses where editing is allowed
     *
     * @return array
     */
    public static function getEditableStatuses(): array
    {
        return [
            static::ADOPTION_STATUS_PRIVATE_DRAFT,
            static::ADOPTION_STATUS_DRAFT,
        ];
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
     * @return LsDoc
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
     * Set identifier
     *
     * @param string $identifier
     *
     * @return LsDoc
     */
    public function setIdentifier($identifier = null)
    {
        if (null !== $identifier) {
            // If the identifier is in the form of a UUID then lower case it
            if ($identifier instanceof Uuid) {
                $identifier = strtolower($identifier->serialize());
            } elseif (is_string($identifier) && Uuid::isValid($identifier)) {
                $identifier = Uuid::fromString($identifier);
                $identifier = strtolower($identifier->serialize());
            }
        }

        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set officialUri
     *
     * @param string $officialUri
     *
     * @return LsDoc
     */
    public function setOfficialUri($officialUri)
    {
        $this->officialUri = $officialUri;

        return $this;
    }

    /**
     * Get officialUri
     *
     * @return string
     */
    public function getOfficialUri()
    {
        return $this->officialUri;
    }

    /**
     * Set creator
     *
     * @param string $creator
     *
     * @return LsDoc
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return string
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set publisher
     *
     * @param string $publisher
     *
     * @return LsDoc
     */
    public function setPublisher($publisher)
    {
        $this->publisher = $publisher;

        return $this;
    }

    /**
     * Get publisher
     *
     * @return string
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return LsDoc
     */
    public function setTitle($title)
    {
        $this->title = substr($title, 0, 120);

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
     * Set version
     *
     * @param string $version
     *
     * @return LsDoc
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return LsDoc
     */
    public function setDescription($description)
    {
        $this->description = substr($description, 0, 300);

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

    /**
     * Set subject
     *
     * @param string $subject
     *
     * @return LsDoc
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set subjectUri
     *
     * @param string $subjectUri
     *
     * @return LsDoc
     */
    public function setSubjectUri($subjectUri)
    {
        $this->subjectUri = $subjectUri;

        return $this;
    }

    /**
     * Get subjectUri
     *
     * @return string
     */
    public function getSubjectUri()
    {
        return $this->subjectUri;
    }

    /**
     * Set adoptionStatus
     *
     * @param string $adoptionStatus
     *
     * @return LsDoc
     */
    public function setAdoptionStatus($adoptionStatus)
    {
        // Check that adoptionStatus is valid
        if (in_array($adoptionStatus, static::getStatuses(), true)) {
            $this->adoptionStatus = $adoptionStatus;

            return $this;
        }

        throw new \InvalidArgumentException('Invalid Adoptions Status of '.$adoptionStatus);
    }

    /**
     * Get adoptionStatus
     *
     * @return string
     */
    public function getAdoptionStatus()
    {
        return $this->adoptionStatus;
    }

    /**
     * Set statusStart
     *
     * @param \DateTime $statusStart
     *
     * @return LsDoc
     */
    public function setStatusStart($statusStart)
    {
        $this->statusStart = $statusStart;

        return $this;
    }

    /**
     * Get statusStart
     *
     * @return \DateTime
     */
    public function getStatusStart()
    {
        return $this->statusStart;
    }

    /**
     * Set statusEnd
     *
     * @param \DateTime $statusEnd
     *
     * @return LsDoc
     */
    public function setStatusEnd($statusEnd)
    {
        $this->statusEnd = $statusEnd;

        return $this;
    }

    /**
     * Get statusEnd
     *
     * @return \DateTime
     */
    public function getStatusEnd()
    {
        return $this->statusEnd;
    }

    /**
     * Set note
     *
     * @param string $note
     *
     * @return LsDoc
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Add createChildItem
     *
     * @param LsItem $topLsItem
     * @param LsDefAssociationGrouping|null $assocGroup
     * @param int|null $sequenceNumber
     *
     * @return LsAssociation
     */
    public function createChildItem(LsItem $topLsItem, ?LsDefAssociationGrouping $assocGroup = null, ?int $sequenceNumber = null)
    {
        $association = new LsAssociation();
        $association->setLsDoc($this);
        $association->setOriginLsItem($topLsItem);
        $association->setType(LsAssociation::CHILD_OF);
        $association->setDestinationLsDoc($this);
        if (null !== $sequenceNumber) {
            $association->setSequenceNumber($sequenceNumber);
        }

        // PW: set assocGroup if provided and non-null
        if ($assocGroup !== null) {
            $association->setGroup($assocGroup);
        }

        $topLsItem->addAssociation($association);
        $this->addInverseAssociation($association);

        return $association;
    }

    /**
     * Add topLsItem
     *
     * @param LsItem $topLsItem
     * @param LsDefAssociationGrouping|null $assocGroup
     *
     * @return LsDoc
     */
    public function addTopLsItem(LsItem $topLsItem, ?LsDefAssociationGrouping $assocGroup = null)
    {
        $this->createChildItem($topLsItem, $assocGroup);

        return $this;
    }

    /**
     * Get topLsItems
     *
     * @return LsItem[]|\Doctrine\Common\Collections\Collection
     */
    public function getTopLsItems()
    {
        $topAssociations = new ArrayCollection();

        $associations = $this->getInverseAssociations();
        foreach ($associations as $association) {
            /** @var LsAssociation $association */
            if ($association->getLsDoc()->getId() == $this->getId()) {
                if ($association->getType() === LsAssociation::CHILD_OF) {
                    $topAssociations->add($association->getOriginLsItem());
                }
            }
        }

        $iterator = $topAssociations->getIterator();
        $iterator->uasort(function (LsItem $a, LsItem $b) {
            // rank
            if (!empty($a->getRank()) && !empty($b->getRank())) {
                if ($a->getRank() !== $b->getRank()) {
                    return ($a < $b) ? -1 : 1;
                } // else fall through to next check
            } elseif (!empty($a->getRank()) || !empty($b->getRank())) {
                return (!empty($a->getRank())) ? -1 : 1;
            }

            // listEnumInSource
            // humanCodingScheme

            return 0;
        });
        $topAssociations = new ArrayCollection(iterator_to_array($iterator));

        return $topAssociations;
    }

    /**
     * Get topLsItems ids
     *
     * @return array|int[]
     */
    public function getTopLsItemIds()
    {
        $items = $this->getTopLsItems()->map(function (LsItem $item) {
            return [
                'id' => $item->getId(),
                'rank' => $item->getRank(),
                'listEnumInSource' => $item->getListEnumInSource(),
                'humanCodingScheme' => $item->getHumanCodingScheme(),
            ];
        })->toArray();
        Compare::sortArrayByFields($items, ['rank', 'listEnumInSource', 'humanCodingScheme']);
        $items = new ArrayCollection($items);

        $ids = $items->map(function ($item) {
            return $item['id'];
        });

        return $ids->toArray();
    }

    /**
     * Add lsItem
     *
     * @param LsItem $lsItem
     *
     * @return LsDoc
     */
    public function addLsItem(LsItem $lsItem)
    {
        $this->lsItems[] = $lsItem;

        return $this;
    }

    /**
     * Remove lsItem
     *
     * @param LsItem $lsItem
     */
    public function removeLsItem(LsItem $lsItem)
    {
        $this->lsItems->removeElement($lsItem);
    }

    /**
     * Get lsItems
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLsItems()
    {
        return $this->lsItems;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return LsDoc
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
     * Add association
     *
     * @param \CftfBundle\Entity\LsAssociation $association
     *
     * @return LsDoc
     */
    public function addAssociation(\CftfBundle\Entity\LsAssociation $association)
    {
        $this->associations[] = $association;

        return $this;
    }

    /**
     * Remove association
     *
     * @param \CftfBundle\Entity\LsAssociation $association
     */
    public function removeAssociation(\CftfBundle\Entity\LsAssociation $association)
    {
        $this->associations->removeElement($association);
    }

    /**
     * Get associations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAssociations()
    {
        return $this->associations;
    }

    /**
     * Add inverseAssociation
     *
     * @param \CftfBundle\Entity\LsAssociation $inverseAssociation
     *
     * @return LsDoc
     */
    public function addInverseAssociation(\CftfBundle\Entity\LsAssociation $inverseAssociation)
    {
        $this->inverseAssociations[] = $inverseAssociation;

        return $this;
    }

    /**
     * Remove inverseAssociation
     *
     * @param \CftfBundle\Entity\LsAssociation $inverseAssociation
     */
    public function removeInverseAssociation(\CftfBundle\Entity\LsAssociation $inverseAssociation)
    {
        $this->inverseAssociations->removeElement($inverseAssociation);
    }

    /**
     * Get inverseAssociations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInverseAssociations()
    {
        return $this->inverseAssociations;
    }

    /**
     * Add docAssociation
     *
     * @param \CftfBundle\Entity\LsAssociation $docAssociation
     *
     * @return LsDoc
     */
    public function addDocAssociation(\CftfBundle\Entity\LsAssociation $docAssociation)
    {
        $this->docAssociations[] = $docAssociation;

        return $this;
    }

    /**
     * Remove docAssociation
     *
     * @param \CftfBundle\Entity\LsAssociation $docAssociation
     */
    public function removeDocAssociation(\CftfBundle\Entity\LsAssociation $docAssociation)
    {
        $this->docAssociations->removeElement($docAssociation);
    }

    /**
     * Get docAssociations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocAssociations()
    {
        return $this->docAssociations;
    }

    /**
     * Add a document attribute
     *
     * @param string $name
     * @param string $value
     *
     * @return LsDoc
     */
    public function setAttribute($name, $value) {
        // if attribute already exists, update it
        if ($this->attributes->containsKey($name)) {
            $this->attributes->get($name)->setValue($value);
        } else {
            $this->attributes->set($name, new LsDocAttribute($this, $name, $value));
        }

        return $this;
    }

    /**
     * Remove a document attribute
     *
     * @param $name
     *
     * @return $this
     */
    public function removeAttribute($name) {
        // TODO (PW): does this really remove the item? I did add "orphanRemoval=true" to the attributes field above
        $this->attributes->remove($name);

        return $this;
    }

    /**
     * Get the value of an attribute
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getAttribute($name) {
        if ($this->attributes->containsKey($name)) {
            return $this->attributes->get($name)->getValue();
        }

        return null;
    }

    /**
     * Use attributes fields to save the identifiers, urls, and titles of a list of associated documents on different servers
     * Note that this fn is protected; addExternalDoc and removeExternalDoc are the public functions
     *
     * @param array $externalDocs
     *
     * @return $this
     */
    protected function setExternalDocs($externalDocs) {
        // save all ed's passed in
        $i = 0;
        foreach ($externalDocs as $identifier => $ad) {
            $this->setAttribute("externalDoc$i", $identifier.'|'.$ad['autoLoad'].'|'.$ad['url'].'|'.$ad['title']);
            // title may get cut off if it's very long, but that's OK.
            ++$i;
        }

        // remove any remaining, now-extraneous ed's
        do {
            if (!empty($this->getAttribute("externalDoc$i"))) {
                $this->removeAttribute("externalDoc$i");
            }
            ++$i;
        } while ($i < 1000);    // we should always break, but include this as a safety valve

        return $this;
    }

    /**
     * Add an associated doc
     *
     * @param string $identifier
     * @param string $autoLoad - "true" or "false"
     * @param string $url
     * @param string $title
     *
     * @return bool
     */
    public function addExternalDoc($identifier, $autoLoad, $url, $title) {
        if (empty($identifier) || empty($autoLoad) || empty($url) || empty($title)) {
            return false;
        }

        // get the doc's existing externalDocs; if this new doc isn't already there, add it
        $externalDocs = $this->getExternalDocs();
        $externalDocs[$identifier] = [
            'autoLoad' => $autoLoad,
            'url' => $url,
            'title' => $title
        ];
        $this->setExternalDocs($externalDocs);

        return true;
    }

    public function setExternalDocAutoLoad($identifier, $autoLoad) {
        $externalDocs = $this->getExternalDocs();
        if (empty($externalDocs[$identifier])) {
            return false;
        }
        $externalDocs[$identifier]['autoLoad'] = $autoLoad;
        $this->setExternalDocs($externalDocs);
    }

    /**
     * Remove an associated doc
     */
    public function removeExternalDoc($identifier) {
        $externalDocs = $this->getExternalDocs();
        if (empty($externalDocs[$identifier])) {
            unset($externalDocs[$identifier]);
            $this->setExternalDocs($externalDocs);
        }
    }

    /**
     * Get the list of associated documents for this document
     *
     * @return array (which could be empty)
     */
    public function getExternalDocs() {
        $externalDocs = [];
        for ($i = 0; $i < 1000; ++$i) {
            // look for next externalDoc
            $ed = $this->getAttribute("externalDoc$i");

            // if found, parse the ed, which should have the form "identifier|url|title"
            if (!empty($ed) && preg_match("/^(.+?)\|(true|false)\|(.+?)\|(.*)/", $ed, $matches)) {
                $externalDocs[$matches[1]] = [
                    'autoLoad' => $matches[2],
                    'url' => $matches[3],
                    'title' => $matches[4]
                ];
            }
        }

        return $externalDocs;
    }

    /**
     * @return string
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param string $language
     *
     * @return LsDoc
     */
    public function setLanguage($language) {
        $this->language = $language;
        return $this;
    }

    /**
     * Determine if the LsDoc is editable
     *
     * @return bool
     */
    public function canEdit() {
        return is_null($this->adoptionStatus) || in_array($this->adoptionStatus, static::getEditableStatuses(), true);
    }

    /**
     * @return LsDefSubject[]|ArrayCollection
     */
    public function getSubjects() {
        return $this->subjects;
    }

    /**
     * @param LsDefSubject[]|ArrayCollection $subjects
     *
     * @return LsDoc
     */
    public function setSubjects($subjects) {
        $this->subjects = $subjects;
        return $this;
    }

    /**
     * @param LsDefSubject
     *
     * @return LsDoc
     */
    public function addSubject(LsDefSubject $subject) {
        $this->subjects[] = $subject;
        return $this;
    }

    /**
     * Get the organization owner for the framework
     *
     * @return \Salt\UserBundle\Entity\Organization
     */
    public function getOrg() {
        return $this->org;
    }

    /**
     * Set the organization owner for the framework
     *
     * @param \Salt\UserBundle\Entity\Organization $org
     *
     * @return LsDoc
     */
    public function setOrg(Organization $org = null) {
        $this->org = $org;

        return $this;
    }

    /**
     * Get the user owner for the framework
     *
     * @return \Salt\UserBundle\Entity\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Set the user owner for the framework
     *
     * @param \Salt\UserBundle\Entity\User $user
     *
     * @return LsDoc
     */
    public function setUser(User $user = null) {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the owner of the framework
     *
     * @return Organization|User
     */
    public function getOwner() {
        if (null !== $this->org) {
            return $this->org;
        } else {
            return $this->user;
        }
    }

    /**
     * @return Collection|UserDocAcl[]
     */
    public function getDocAcls() {
        return $this->docAcls;
    }

    /**
     * Returns 'user' or 'organization' based on which value exists
     *
     * @return string
     */
    public function getOwnedBy(): ?string {
        if (!empty($this->ownedBy)) {
            return $this->ownedBy;
        } else {
            if ($this->getOrg()) {
                return 'organization';
            } elseif ($this->getUser()) {
                return 'user';
            } else {
                return null;
            }
        }
    }

    /**
     * @param string $ownedBy
     *
     * @return LsDoc
     */
    public function setOwnedBy($ownedBy) {
        $this->ownedBy = $ownedBy;

        return $this;
    }

    /**
     * @return LsDefAssociationGrouping[]|Collection
     */
    public function getAssociationGroupings()
    {
        return $this->associationGroupings;
    }

    /**
     * @param LsDefAssociationGrouping[]|Collection $associationGroupings
     *
     * @return LsDoc
     */
    public function setAssociationGroupings($associationGroupings)
    {
        $this->associationGroupings = $associationGroupings;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrlName(): ?string
    {
        return $this->urlName;
    }

    /**
     * @param null|string $urlName
     *
     * @return $this
     */
    public function setUrlName(?string $urlName=null): LsDoc
    {
        $this->urlName = $urlName;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        if (null !== $this->urlName) {
            return $this->getUrlName();
        }

        return $this->getId();
    }
}
