<?php

namespace CftfBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
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
 * @UniqueEntity("urlName")
 * @UniqueEntity("identifier")
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
 *     "cfPackageUri",
 *     exp="service('salt.api.v1p0.utils').getLinkUri(object, 'api_v1p0_cfpackage')",
 *     options={
 *         @Serializer\SerializedName("CFPackageURI"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "subjectUri",
 *     exp="(object.getSubjects().count()===0)?null:service('salt.api.v1p0.utils').getLinkUriList(object.getSubjects())",
 *     options={
 *         @Serializer\SerializedName("subjectURI"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "licenseUri",
 *     exp="service('salt.api.v1p0.utils').getLinkUri(object.getLicence())",
 *     options={
 *         @Serializer\SerializedName("licenseURI"),
 *         @Serializer\Expose()
 *     }
 * )
 */
class LsDoc extends AbstractLsBase implements CaseApiInterface
{
    public const ADOPTION_STATUS_PRIVATE_DRAFT = 'Private Draft';
    public const ADOPTION_STATUS_DRAFT = 'Draft';
    public const ADOPTION_STATUS_ADOPTED = 'Adopted';
    public const ADOPTION_STATUS_DEPRECATED = 'Deprecated';

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
     *
     * @Serializer\Exclude()
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
     * @Serializer\Exclude()
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
     * @Serializer\Expose("object.getSubjects().count()>0")
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
     * @Serializer\Type("DateTime<'Y-m-d'>")
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
     * @Serializer\Type("DateTime<'Y-m-d'>")
     */
    private $statusEnd;

    /**
     * @var LsDefLicence
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDefLicence")
     * @ORM\JoinColumn(name="licence_id", referencedColumnName="id", nullable=true)
     *
     * @Serializer\Exclude()
     */
    private $licence;

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
     * @var ImportLog[]|Collection
     * @ORM\OneToMany(targetEntity="CftfBundle\Entity\ImportLog", mappedBy="lsDoc", indexBy="lsDoc", fetch="EXTRA_LAZY")
     *
     * @Serializer\Exclude()
     */
    protected $importLogs;

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
     *
     * @param string|Uuid|null $identifier
     */
    public function __construct($identifier = null)
    {
        parent::__construct($identifier);

        $this->lsItems = new ArrayCollection();
        $this->docAssociations = new ArrayCollection();
        $this->associations = new ArrayCollection();
        $this->inverseAssociations = new ArrayCollection();
        $this->attributes = new ArrayCollection();
        $this->subjects = new ArrayCollection();
        $this->docAcls = new ArrayCollection();
        $this->importLogs = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getUri();
    }

    /**
     * @return bool
     */
    public function isLsDoc(): bool
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

    public function isDraft(): bool
    {
        if (null === $this->adoptionStatus || '' === $this->adoptionStatus) {
            return true;
        }

        return in_array($this->adoptionStatus, static::getEditableStatuses(), true);
    }

    public function isAdopted(): bool
    {
        return $this->adoptionStatus === static::ADOPTION_STATUS_ADOPTED;
    }

    public function isDeprecated(): bool
    {
        return $this->adoptionStatus === static::ADOPTION_STATUS_DEPRECATED;
    }

    /**
     * Set officialUri
     *
     * @param string $officialUri
     *
     * @return LsDoc
     */
    public function setOfficialUri($officialUri): LsDoc
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
    public function setCreator($creator): LsDoc
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
    public function setPublisher($publisher): LsDoc
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
    public function setTitle($title): LsDoc
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
    public function setVersion($version): LsDoc
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
    public function setDescription($description): LsDoc
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
    public function setSubject($subject): LsDoc
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
    public function setSubjectUri($subjectUri): LsDoc
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
    public function setAdoptionStatus($adoptionStatus): LsDoc
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
    public function setStatusStart($statusStart): LsDoc
    {
        $this->statusStart = $statusStart;

        return $this;
    }

    /**
     * Get statusStart
     *
     * @return \DateTime
     */
    public function getStatusStart(): ?\DateTime
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
    public function setStatusEnd($statusEnd): LsDoc
    {
        $this->statusEnd = $statusEnd;

        return $this;
    }

    /**
     * Get statusEnd
     *
     * @return \DateTime
     */
    public function getStatusEnd(): ?\DateTime
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
    public function setNote($note): LsDoc
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
     * @param int|null $sequenceNumber
     *
     * @return LsDoc
     */
    public function addTopLsItem(LsItem $topLsItem, ?LsDefAssociationGrouping $assocGroup = null, ?int $sequenceNumber = null): LsDoc
    {
        $this->createChildItem($topLsItem, $assocGroup, $sequenceNumber);

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
    public function addLsItem(LsItem $lsItem): LsDoc
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
     * Add association
     *
     * @param \CftfBundle\Entity\LsAssociation $association
     *
     * @return LsDoc
     */
    public function addAssociation(\CftfBundle\Entity\LsAssociation $association): LsDoc
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
     * @return \Doctrine\Common\Collections\Collection|LsAssociation[]
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
    public function addInverseAssociation(\CftfBundle\Entity\LsAssociation $inverseAssociation): LsDoc
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
    public function addDocAssociation(\CftfBundle\Entity\LsAssociation $docAssociation): LsDoc
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
    public function setAttribute($name, $value): LsDoc
    {
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
    public function removeAttribute($name): LsDoc
    {
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
    public function getAttribute($name): ?string
    {
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
    protected function setExternalDocs($externalDocs): LsDoc
    {
        // save all ed's passed in
        $i = 0;
        foreach ($externalDocs as $identifier => $ad) {
            $this->setAttribute("externalDoc$i", $identifier.'|'.$ad['autoLoad'].'|'.$ad['url'].'|'.$ad['title']);
            // title may get cut off if it's very long, but that's OK.
            ++$i;
        }

        // remove any remaining, now-extraneous ed's
        do {
            if ($this->attributes->containsKey("externalDoc$i")) {
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
    public function addExternalDoc($identifier, $autoLoad, $url, $title): bool
    {
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

    public function setExternalDocAutoLoad($identifier, $autoLoad)
    {
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
    public function removeExternalDoc($identifier)
    {
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
    public function getExternalDocs()
    {
        $externalDocs = [];

        $attrKeys = $this->attributes->getKeys();
        foreach ($attrKeys as $key) {
            if (0 === strpos($key, 'externalDoc')) {
                $ed = $this->getAttribute($key);

                if (null !== $ed && preg_match("/^(.+?)\|(true|false)\|(.+?)\|(.*)/", $ed, $matches)) {
                    $externalDocs[$matches[1]] = [
                        'autoLoad' => $matches[2],
                        'url' => $matches[3],
                        'title' => $matches[4]
                    ];
                }
            }
        }

        return $externalDocs;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     *
     * @return LsDoc
     */
    public function setLanguage($language): LsDoc
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Determine if the LsDoc is editable
     *
     * @return bool
     */
    public function canEdit(): bool
    {
        return is_null($this->adoptionStatus) || in_array($this->adoptionStatus, static::getEditableStatuses(), true);
    }

    /**
     * @return LsDefSubject[]|ArrayCollection
     */
    public function getSubjects()
    {
        return $this->subjects;
    }

    /**
     * @param LsDefSubject[]|ArrayCollection $subjects
     *
     * @return LsDoc
     */
    public function setSubjects($subjects): LsDoc
    {
        $this->subjects = $subjects;

        return $this;
    }

    /**
     * @param LsDefSubject
     *
     * @return LsDoc
     */
    public function addSubject(LsDefSubject $subject): LsDoc
    {
        $this->subjects[] = $subject;

        return $this;
    }

    /**
     * Get the organization owner for the framework
     *
     * @return \Salt\UserBundle\Entity\Organization
     */
    public function getOrg()
    {
        return $this->org;
    }

    /**
     * Set the organization owner for the framework
     *
     * @param \Salt\UserBundle\Entity\Organization $org
     *
     * @return LsDoc
     */
    public function setOrg(Organization $org = null): LsDoc
    {
        $this->org = $org;

        return $this;
    }

    /**
     * Get the user owner for the framework
     *
     * @return \Salt\UserBundle\Entity\User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set the user owner for the framework
     *
     * @param \Salt\UserBundle\Entity\User|null $user
     *
     * @return LsDoc
     */
    public function setUser(?User $user = null): LsDoc
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the owner of the framework
     *
     * @return Organization|User
     */
    public function getOwner()
    {
        if (null !== $this->org) {
            return $this->org;
        }

        return $this->user;
    }

    /**
     * @return Collection|UserDocAcl[]
     */
    public function getDocAcls(): iterable
    {
        return $this->docAcls;
    }

    /**
     * @return Collection|ImportLog[]
     */
    public function getImportLogs(): iterable
    {
        return $this->importLogs;
    }

    /**
     * Returns 'user' or 'organization' based on which value exists
     *
     * @return string
     */
    public function getOwnedBy(): ?string
    {
        if (!empty($this->ownedBy)) {
            return $this->ownedBy;
        }

        if ($this->getOrg()) {
            return 'organization';
        }

        if ($this->getUser()) {
            return 'user';
        }

        return null;
    }

    /**
     * @param string $ownedBy
     *
     * @return LsDoc
     */
    public function setOwnedBy($ownedBy): LsDoc
    {
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
    public function setAssociationGroupings($associationGroupings): LsDoc
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
    public function setUrlName(?string $urlName = null): LsDoc
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

    /**
     * @return LsDefLicence|null
     */
    public function getLicence(): ?LsDefLicence
    {
        return $this->licence;
    }

    /**
     * @param LsDefLicence $licence
     *
     * @return LsDoc
     */
    public function setLicence($licence): LsDoc
    {
        $this->licence = $licence;

        return $this;
    }

    /**
     * @param Uuid|string|null $identifier
     *
     * @return LsItem
     */
    public function createItem($identifier = null): LsItem
    {
        $item = new LsItem($identifier);
        $item->setLsDoc($this);

        return $item;
    }

    /**
     * @param Uuid|string|null $identifier
     *
     * @return LsAssociation
     */
    public function createAssociation($identifier = null): LsAssociation
    {
        $association = new LsAssociation($identifier);
        $association->setLsDoc($this);

        return $association;
    }
}
