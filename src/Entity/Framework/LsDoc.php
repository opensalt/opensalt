<?php

namespace App\Entity\Framework;

use App\Entity\LockableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use App\Entity\User\Organization;
use App\Entity\User\User;
use App\Entity\User\UserDocAcl;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use App\Util\Compare;

/**
 * LsDoc
 *
 * @ORM\Table(name="ls_doc")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\LsDocRepository")
 * @UniqueEntity("uri")
 * @UniqueEntity("urlName")
 * @UniqueEntity("identifier")
 *
 * @Serializer\VirtualProperty(
 *     "cfPackageUri",
 *     exp="service('App\\Service\\Api1Uris').getLinkUri(object, 'api_v1p0_cfpackage')",
 *     options={
 *         @Serializer\SerializedName("CFPackageURI"),
 *         @Serializer\Expose(),
 *         @Serializer\Groups({"LsDoc"})
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "subjectUri",
 *     exp="(object.getSubjects().count()===0)?null:service('App\\Service\\Api1Uris').getLinkUriList(object.getSubjects())",
 *     options={
 *         @Serializer\SerializedName("subjectURI"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "licenseUri",
 *     exp="service('App\\Service\\Api1Uris').getLinkUri(object.getLicence())",
 *     options={
 *         @Serializer\SerializedName("licenseURI"),
 *         @Serializer\Expose()
 *     }
 * )
 */
class LsDoc extends AbstractLsBase implements CaseApiInterface, LockableInterface
{
    public const ADOPTION_STATUS_PRIVATE_DRAFT = 'Private Draft';
    public const ADOPTION_STATUS_DRAFT = 'Draft';
    public const ADOPTION_STATUS_ADOPTED = 'Adopted';
    public const ADOPTION_STATUS_DEPRECATED = 'Deprecated';

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User\Organization", inversedBy="frameworks")
     * @ORM\JoinColumn(name="org_id", referencedColumnName="id", nullable=true)
     *
     * @Assert\Type(Organization::class)
     *
     * @Serializer\Exclude()
     */
    protected $org;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User", inversedBy="frameworks")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     *
     * @Assert\Type(User::class)
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
     *
     * @Serializer\Expose()
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
     * @ORM\ManyToMany(targetEntity="LsDefSubject")
     * @ORM\JoinTable(name="ls_doc_subject",
     *      joinColumns={@ORM\JoinColumn(name="ls_doc_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="subject_id", referencedColumnName="id")}
     * )
     *
     * @Assert\All({
     *     @Assert\Type(LsDefSubject::class)
     * })
     *
     * @Serializer\Expose("object.getSubjects().count()>0")
     * @Serializer\SerializedName("subject")
     * @Serializer\Type("array<string>")
     * @Serializer\Accessor(getter="getSubjectTextArray")
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
     * @Assert\Choice(callback = "getStatuses")
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
     * @ORM\ManyToOne(targetEntity="LsDefLicence")
     * @ORM\JoinColumn(name="licence_id", referencedColumnName="id", nullable=true)
     *
     * @Serializer\Exclude()
     */
    private $licence;

    /**
     * @var FrameworkType
     *
     * @ORM\ManyToOne(targetEntity="FrameworkType", cascade = {"persist"})
     * @ORM\JoinColumn(name="frameworktype_id", referencedColumnName="id", nullable=true)
     *
     * @Serializer\Exclude()
     */
    private $frameworkType;

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
     * @ORM\OneToMany(targetEntity="LsItem", mappedBy="lsDoc", indexBy="id", fetch="EXTRA_LAZY")
     *
     * @Assert\All({
     *     @Assert\Type(LsItem::class)
     * })
     *
     * @Serializer\Exclude()
     */
    private $lsItems;

    /**
     * @var Collection|LsAssociation[]
     *
     * @ORM\OneToMany(targetEntity="LsAssociation", mappedBy="lsDoc", indexBy="id", fetch="EXTRA_LAZY")
     *
     * @Assert\All({
     *     @Assert\Type(LsAssociation::class)
     * })
     *
     * @Serializer\Exclude()
     */
    private $docAssociations;

    /**
     * @var Collection|LsAssociation[]
     *
     * @Assert\All({
     *     @Assert\Type(LsAssociation::class)
     * })
     *
     * @ORM\OneToMany(targetEntity="LsAssociation", mappedBy="originLsDoc", indexBy="id", cascade={"persist"})
     *
     * @Serializer\Exclude()
     */
    private $associations;

    /**
     * @var Collection|LsAssociation[]
     *
     * @Assert\All({
     *     @Assert\Type(LsAssociation::class)
     * })
     *
     * @ORM\OneToMany(targetEntity="LsAssociation", mappedBy="destinationLsDoc", indexBy="id", cascade={"persist"})
     *
     * @Serializer\Exclude()
     */
    private $inverseAssociations;

    /**
     * @var LsDocAttribute[]|ArrayCollection
     *
     * @Assert\All({
     *     @Assert\Type(LsDocAttribute::class)
     * })
     *
     * @ORM\OneToMany(targetEntity="LsDocAttribute", mappedBy="lsDoc", cascade={"ALL"}, indexBy="attribute", orphanRemoval=true)
     *
     * @Serializer\Exclude()
     */
    private $attributes;

    /**
     * @var UserDocAcl[]|Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\User\UserDocAcl", mappedBy="lsDoc", indexBy="user", fetch="EXTRA_LAZY")
     *
     * @Assert\All({
     *     @Assert\Type(UserDocAcl::class)
     * })
     *
     * @Serializer\Exclude()
     */
    protected $docAcls;

    /**
     * @var ImportLog[]|Collection
     *
     * @ORM\OneToMany(targetEntity="ImportLog", mappedBy="lsDoc", indexBy="lsDoc", fetch="EXTRA_LAZY")
     *
     * @Assert\All({
     *     @Assert\Type(ImportLog::class)
     * })
     *
     * @Serializer\Exclude()
     */
    protected $importLogs;

    /**
     * @var LsDefAssociationGrouping[]|Collection
     *
     * @ORM\OneToMany(targetEntity="LsDefAssociationGrouping", mappedBy="lsDoc", indexBy="id", fetch="EXTRA_LAZY")
     *
     * @Assert\All({
     *     @Assert\Type(LsDefAssociationGrouping::class)
     * })
     *
     * @Serializer\Exclude()
     */
    protected $associationGroupings;

    /**
     * @var string
     *
     * @Assert\Choice({"organization", "user"})
     *
     * @Serializer\Exclude()
     */
    protected $ownedBy;

    /**
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

    public function __toString(): string
    {
        return $this->getUri();
    }

    public function isLsDoc(): bool
    {
        return true;
    }

    /**
     * Get the list of Adoption Statuses.
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
     * Get the list of Adoption Statuses where editing is allowed.
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

    public function setOfficialUri(?string $officialUri): LsDoc
    {
        $this->officialUri = $officialUri;

        return $this;
    }

    public function getOfficialUri(): ?string
    {
        return $this->officialUri;
    }

    public function setCreator(?string $creator): LsDoc
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function setPublisher(?string $publisher): LsDoc
    {
        $this->publisher = $publisher;

        return $this;
    }

    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function setTitle(string $title): LsDoc
    {
        $this->title = mb_substr($title, 0, 120);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getShortStatement(): string
    {
        return mb_substr($this->title, 0, 60);
    }

    public function setVersion(?string $version): LsDoc
    {
        $this->version = $version;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setDescription(?string $description): LsDoc
    {
        $this->description = mb_substr($description, 0, 300);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setSubject(?string $subject): LsDoc
    {
        $this->subject = $subject;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubjectUri(?string $subjectUri): LsDoc
    {
        $this->subjectUri = $subjectUri;

        return $this;
    }

    public function getSubjectUri(): ?string
    {
        return $this->subjectUri;
    }

    public function setAdoptionStatus(string $adoptionStatus): LsDoc
    {
        // Check that adoptionStatus is valid
        if (in_array($adoptionStatus, static::getStatuses(), true)) {
            $this->adoptionStatus = $adoptionStatus;

            return $this;
        }

        throw new \InvalidArgumentException('Invalid Adoptions Status of '.$adoptionStatus);
    }

    public function getAdoptionStatus(): ?string
    {
        return $this->adoptionStatus;
    }

    /**
     * Set statusStart
     *
     * @param \DateTime $statusStart
     */
    public function setStatusStart($statusStart): LsDoc
    {
        $this->statusStart = $statusStart;

        return $this;
    }

    /**
     * Get statusStart
     */
    public function getStatusStart(): ?\DateTime
    {
        return $this->statusStart;
    }

    /**
     * Set statusEnd
     *
     * @param \DateTime $statusEnd
     */
    public function setStatusEnd($statusEnd): LsDoc
    {
        $this->statusEnd = $statusEnd;

        return $this;
    }

    public function getStatusEnd(): ?\DateTime
    {
        return $this->statusEnd;
    }

    public function setNote(?string $note): LsDoc
    {
        $this->note = $note;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function createChildItem(LsItem $topLsItem, ?LsDefAssociationGrouping $assocGroup = null, ?int $sequenceNumber = null): LsAssociation
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

    public function addTopLsItem(LsItem $topLsItem, ?LsDefAssociationGrouping $assocGroup = null, ?int $sequenceNumber = null): LsDoc
    {
        $this->createChildItem($topLsItem, $assocGroup, $sequenceNumber);

        return $this;
    }

    /**
     * @return LsItem[]|Collection
     */
    public function getTopLsItems()
    {
        $topAssociations = [];

        $associations = $this->getInverseAssociations();
        foreach ($associations as $association) {
            /** @var LsAssociation $association */
            if (null === $association->getLsDoc() || null === $association->getOriginLsItem()) {
                continue;
            }

            if ($association->getLsDoc()->getId() === $this->getId()) {
                if ($association->getType() === LsAssociation::CHILD_OF) {
                    $topAssociations[] = [
                        'sequenceNumber' => $association->getSequenceNumber(),
                        'enum' => $association->getOriginLsItem()->getListEnumInSource(),
                        'hcs' => $association->getOriginLsItem()->getHumanCodingScheme(),
                        'item' => $association->getOriginLsItem(),
                    ];
                }
            }
        }

        Compare::sortArrayByFields($topAssociations, ['sequenceNumber', 'enum', 'hcs']);

        $orderedList = array_map(function ($rec) {
            return $rec['item'];
        }, $topAssociations);

        $topAssociations = new ArrayCollection($orderedList);

        return $topAssociations;
    }

    /**
     * @return array|int[]
     */
    public function getTopLsItemIds(): array
    {
        $ids = $this->getTopLsItems()->map(function (LsItem $item) {
            return $item->getId();
        })->toArray();

        return $ids;
    }

    public function addLsItem(LsItem $lsItem): LsDoc
    {
        $this->lsItems[] = $lsItem;

        return $this;
    }

    public function removeLsItem(LsItem $lsItem): void
    {
        $this->lsItems->removeElement($lsItem);
    }

    /**
     * Get lsItems
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLsItems(): Collection
    {
        return $this->lsItems;
    }

    public function addAssociation(LsAssociation $association): LsDoc
    {
        $this->associations[] = $association;

        return $this;
    }

    public function removeAssociation(LsAssociation $association)
    {
        $this->associations->removeElement($association);
    }

    /**
     * @return Collection|LsAssociation[]
     */
    public function getAssociations()
    {
        return $this->associations;
    }

    public function addInverseAssociation(LsAssociation $inverseAssociation): LsDoc
    {
        $this->inverseAssociations[] = $inverseAssociation;

        return $this;
    }

    public function removeInverseAssociation(LsAssociation $inverseAssociation)
    {
        $this->inverseAssociations->removeElement($inverseAssociation);
    }

    public function getInverseAssociations(): Collection
    {
        return $this->inverseAssociations;
    }

    public function addDocAssociation(LsAssociation $docAssociation): LsDoc
    {
        $this->docAssociations[] = $docAssociation;

        return $this;
    }

    public function removeDocAssociation(LsAssociation $docAssociation)
    {
        $this->docAssociations->removeElement($docAssociation);
    }

    public function getDocAssociations(): Collection
    {
        return $this->docAssociations;
    }

    /**
     * Add a document attribute
     *
     * @param string $name
     * @param string $value
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
     * @param string|int $name
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

    public function setExternalDocAutoLoad($identifier, $autoLoad): void
    {
        $externalDocs = $this->getExternalDocs();
        if (empty($externalDocs[$identifier])) {
            return;
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
     */
    public function getExternalDocs(): array
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

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language): LsDoc
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Determine if the LsDoc is editable
     */
    public function canEdit(): bool
    {
        return ($this->adoptionStatus === null) || in_array($this->adoptionStatus, static::getEditableStatuses(), true);
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
     */
    public function setSubjects($subjects): LsDoc
    {
        $this->subjects = $subjects;

        return $this;
    }

    public function addSubject(LsDefSubject $subject): LsDoc
    {
        $this->subjects[] = $subject;

        return $this;
    }

    /**
     * Get the organization owner for the framework
     */
    public function getOrg(): ?Organization
    {
        return $this->org;
    }

    /**
     * Set the organization owner for the framework
     */
    public function setOrg(?Organization $org = null): LsDoc
    {
        $this->org = $org;

        return $this;
    }

    /**
     * Get the user owner for the framework
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set the user owner for the framework
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
     * @return Collection|ArrayCollection|UserDocAcl[]
     */
    public function getDocAcls(): Collection
    {
        return $this->docAcls;
    }

    /**
     * @return Collection|ArrayCollection|ImportLog[]
     */
    public function getImportLogs(): Collection
    {
        return $this->importLogs;
    }

    /**
     * Returns 'user' or 'organization' based on which value exists
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
     * @throws \InvalidArgumentException
     */
    public function setOwnedBy(?string $ownedBy): LsDoc
    {
        if (!in_array($ownedBy, [null, 'organization', 'user'], true)) {
            throw new \InvalidArgumentException('Owner must be "organization" or "user" (or empty)');
        }

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
     */
    public function setAssociationGroupings($associationGroupings): LsDoc
    {
        $this->associationGroupings = $associationGroupings;

        return $this;
    }

    public function getUrlName(): ?string
    {
        return $this->urlName;
    }

    public function setUrlName(?string $urlName = null): LsDoc
    {
        $this->urlName = $urlName;

        return $this;
    }

    public function getSlug(): string
    {
        if (null !== $this->urlName) {
            return $this->getUrlName();
        }

        return $this->getId();
    }

    public function getLicence(): ?LsDefLicence
    {
        return $this->licence;
    }

    /**
     * @param LsDefLicence $licence
     */
    public function setLicence($licence): LsDoc
    {
        $this->licence = $licence;

        return $this;
    }

    /**
     * @return FrameworkType|null
     */
    public function getFrameworkType(): ?FrameworkType
    {
        return $this->frameworkType;
    }

    /**
     * @param FrameworkType $frameworkType
     *
     * @return LsDoc
     */
    public function setFrameworkType($frameworkType): LsDoc
    {
        $this->frameworkType = $frameworkType;

        return $this;
    }

    /**
     * @param Uuid|string|null $identifier
     */
    public function createItem($identifier = null): LsItem
    {
        $item = new LsItem($identifier);
        $item->setLsDoc($this);

        return $item;
    }

    /**
     * @param Uuid|string|null $identifier
     */
    public function createAssociation($identifier = null): LsAssociation
    {
        $association = new LsAssociation($identifier);
        $association->setLsDoc($this);

        return $association;
    }

    public function getSubjectTextArray(): array
    {
        $subjects = [];

        foreach ($this->subjects as $subject) {
            $subjects[] = $subject->getTitle();
        }

        return $subjects;
    }
}
