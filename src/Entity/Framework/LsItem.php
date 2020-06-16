<?php

namespace App\Entity\Framework;

use App\Entity\LockableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="ls_item")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\LsItemRepository")
 * @UniqueEntity("uri")
 *
 * @Serializer\VirtualProperty(
 *     "cfDocumentUri",
 *     exp="service('App\\Service\\Api1Uris').getLinkUri(object.getLsDoc())",
 *     options={
 *         @Serializer\SerializedName("CFDocumentURI"),
 *         @Serializer\Expose(),
 *         @Serializer\Groups({"LsItem"})
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "cfItemType",
 *     exp="object.getItemType()?object.getItemType().getTitle():null",
 *     options={
 *         @Serializer\SerializedName("CFItemType"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "cfItemTypeUri",
 *     exp="service('App\\Service\\Api1Uris').getLinkUri(object.getItemType())",
 *     options={
 *         @Serializer\SerializedName("CFItemTypeURI"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "conceptKeywordsUri",
 *     exp="(object.getConcepts().count()===0)?null:service('App\\Service\\Api1Uris').getLinkUri(object.getConcepts()[0])",
 *     options={
 *         @Serializer\SerializedName("conceptKeywordsURI"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "educationLevel",
 *     exp="service('App\\Service\\Api1Uris').splitByComma(object.getEducationalAlignment())",
 *     options={
 *         @Serializer\SerializedName("educationLevel"),
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
class LsItem extends AbstractLsBase implements CaseApiInterface, LockableInterface
{
    use AccessAdditionalFieldTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="ls_doc_identifier", type="string", length=300, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     *
     * @Serializer\Exclude()
     */
    private $lsDocIdentifier;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ls_doc_uri", type="string", length=300, nullable=true)
     * @Assert\Length(max=300)
     *
     * @Serializer\Exclude()
     */
    private $lsDocUri;

    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="LsDoc", inversedBy="lsItems")
     * @Assert\NotBlank()
     *
     * @Serializer\Exclude()
     */
    private $lsDoc;

    /**
     * @var string|null
     *
     * @ORM\Column(name="human_coding_scheme", type="string", length=50, nullable=true)
     *
     * @Assert\Length(max=50)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("humanCodingScheme")
     */
    private $humanCodingScheme;

    /**
     * @var string|null
     *
     * @ORM\Column(name="list_enum_in_source", type="string", length=20, nullable=true)
     *
     * @Assert\Length(max=20)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("listEnumeration")
     */
    private $listEnumInSource;

    /**
     * @var string
     *
     * @ORM\Column(name="full_statement", type="text", nullable=false)
     *
     * @Assert\NotBlank()
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("fullStatement")
     */
    private $fullStatement;

    /**
     * @var string|null
     *
     * @ORM\Column(name="abbreviated_statement", type="text", nullable=true)
     *
     * @Assert\Length(max=60)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("abbreviatedStatement")
     */
    private $abbreviatedStatement;

    /**
     * @var array
     *
     * @ORM\Column(name="concept_keywords", type="json", nullable=true)
     *
     * @Assert\All({
     *     @Assert\Type("string")
     * })
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("conceptKeywords")
     * @Serializer\Type("array<string>")
     */
    private $conceptKeywords = [];

    /**
     * @var LsDefConcept[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="LsDefConcept")
     * @ORM\JoinTable(name="ls_item_concept",
     *      joinColumns={@ORM\JoinColumn(name="ls_item_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="concept_id", referencedColumnName="id")}
     * )
     *
     * @Serializer\Exclude()
     */
    private $concepts;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("notes")
     */
    private $notes;

    /**
     * @var string|null
     *
     * @ORM\Column(name="language", type="string", length=10, nullable=true)
     *
     * @Assert\Length(max=10)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("language")
     */
    private $language;

    /**
     * @var string|null
     *
     * @ORM\Column(name="educational_alignment", type="string", length=300, nullable=true)
     *
     * @Assert\Length(max=300)
     *
     * @Serializer\Exclude()
     */
    private $educationalAlignment;

    /**
     * @var LsDefItemType|null
     *
     * @ORM\ManyToOne(targetEntity="LsDefItemType")
     * @ORM\JoinColumn(name="item_type_id", referencedColumnName="id")
     *
     * @Serializer\Exclude()
     */
    private $itemType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="item_type_text", type="string", nullable=true)
     *
     * @Assert\Length(max=255)
     *
     * @Serializer\Exclude()
     */
    private $itemTypeText;

    /**
     * @var string|null
     *
     * @ORM\Column(name="alternative_label", type="text", nullable=true)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("alternativeLabel")
     */
    private $alternativeLabel;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="status_start", type="date", nullable=true)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("statusStartDate")
     * @Serializer\AccessType("public_method")
     * @Serializer\Type("DateTime<'Y-m-d'>")
     */
    private ?\DateTimeInterface $statusStart = null;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="status_end", type="date", nullable=true)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("statusEndDate")
     * @Serializer\AccessType("public_method")
     * @Serializer\Type("DateTime<'Y-m-d'>")
     */
    private ?\DateTimeInterface $statusEnd = null;

    /**
     * @var LsDefLicence|null
     *
     * @ORM\ManyToOne(targetEntity="LsDefLicence")
     * @ORM\JoinColumn(name="licence_id", referencedColumnName="id", nullable=true)
     *
     * @Serializer\Exclude()
     */
    private $licence;

    /**
     * @var Collection|LsAssociation[]
     *
     * @ORM\OneToMany(targetEntity="LsAssociation", mappedBy="originLsItem", indexBy="id", cascade={"persist"})
     *
     * @Serializer\Exclude()
     */
    private $associations;

    /**
     * @var Collection|LsAssociation[]
     *
     * @ORM\OneToMany(targetEntity="LsAssociation", mappedBy="destinationLsItem", indexBy="id", cascade={"persist"})
     *
     * @Serializer\Exclude()
     */
    private $inverseAssociations;

    /**
     * @var Collection|CfRubricCriterion[]
     *
     * @ORM\OneToMany(targetEntity="CfRubricCriterion", mappedBy="item")
     *
     * @Serializer\Exclude()
     */
    private $criteria;

    /**
     * LsItem constructor.
     *
     * @param string|UuidInterface|null $identifier
     */
    public function __construct($identifier = null)
    {
        parent::__construct($identifier);

        $this->associations = new ArrayCollection();
        $this->inverseAssociations = new ArrayCollection();
        $this->criteria = new ArrayCollection();
        $this->concepts = new ArrayCollection();
    }

    /**
     * Representation of this item as a string.
     */
    public function __toString()
    {
        return $this->getUri();
    }

    /**
     * Clone the LsItem - Do not carry over any associations.
     */
    public function __clone()
    {
        parent::__clone();

        // Clear values for new item
        $this->associations = new ArrayCollection();
        $this->inverseAssociations = new ArrayCollection();
    }

    /**
     * Create a copy of the lsItem into a new document.
     *
     * @throws \UnexpectedValueException
     */
    public function copyToLsDoc(LsDoc $newLsDoc, ?LsDefAssociationGrouping $assocGroup = null, bool $exactMatchAssocs = true): LsItem
    {
        $newItem = clone $this;

        $newItem->setLsDoc($newLsDoc);

        // Add an "Exact" relationship to the original
        if ($exactMatchAssocs) {
            $exactMatch = $newLsDoc->createAssociation();
            $exactMatch->setOrigin($newItem);
            $exactMatch->setType(LsAssociation::EXACT_MATCH_OF);
            $exactMatch->setDestination($this);

            // PW: set assocGroup if provided and non-null
            // TODO: should the assocGroup be on both associations, or just the first association, or just the inverse association??
            if (null !== $assocGroup) {
                $exactMatch->setGroup($assocGroup);
            }
            $newItem->addAssociation($exactMatch);
            $this->addInverseAssociation($exactMatch);
        }

        $seq = 0;
        foreach ($this->getChildren() as $child) {
            $newChild = $child->copyToLsDoc($newLsDoc, $assocGroup, $exactMatchAssocs);
            $newItem->addChild($newChild, $assocGroup, ++$seq);
        }

        return $newItem;
    }

    /**
     * Create a duplicate of the lsItem into a new document.
     *
     * @throws \UnexpectedValueException
     */
    public function duplicateToLsDoc(LsDoc $newLsDoc, ?LsDefAssociationGrouping $assocGroup = null): LsItem
    {
        $newItem = clone $this;
        $newItem->setLsDoc($newLsDoc);

        foreach ($this->getAssociations() as $association) {
            if (LsAssociation::CHILD_OF === $association->getType()) {
                continue;
            }

            $newAssoc = $newLsDoc->createAssociation();
            $newAssoc->setOrigin($newItem);
            $newAssoc->setType($association->getType());
            $newAssoc->setDestination($association->getDestination(), $association->getDestinationNodeIdentifier());
            $newItem->addAssociation($newAssoc);
        }

        foreach ($this->getChildren() as $child) {
            $newChild = $child->duplicateToLsDoc($newLsDoc, $assocGroup);
            $newItem->addChild($newChild, $assocGroup);
        }

        return $newItem;
    }

    /**
     * @param UuidInterface|string|null $identifier
     */
    public function createItem($identifier = null): LsItem
    {
        return $this->getLsDoc()->createItem($identifier);
    }

    /**
     * @param UuidInterface|string|null $identifier
     */
    public function createAssociation($identifier = null): LsAssociation
    {
        return $this->getLsDoc()->createAssociation($identifier);
    }

    public function getGroupedAssociations(): array
    {
        $groups = [
//            'Children' => $this->getChildren(),
//            'Parent' => $this->getLsItemParent(),
        ];

//        $topItems = $this->getTopItemOf();
//        foreach ($topItems as $item) {
//            $groups['Parent']->add($item);
//        }
//        if ($groups['Parent']->isEmpty()) {
//            $groups['Parent']->add($this->getLsDoc());
//        }

        $typeList = LsAssociation::allTypes();
        foreach ($typeList as $type) {
            $groups[$type] = new ArrayCollection();
            $assocName = LsAssociation::inverseName($type);
            if (null === $assocName) {
                $assocName = 'Inverse '.$type;
            }
            $groups[$assocName] = new ArrayCollection();
        }

        $associations = $this->getAssociations();
        foreach ($associations as $association) {
            /** @var LsAssociation $association */
            if ($association->getLsDoc()->getId() !== $this->getLsDoc()->getId()) {
                continue;
            }
            $groups[$association->getType()]->add($association);
        }

        $associations = $this->getInverseAssociations();
        foreach ($associations as $association) {
            /** @var LsAssociation $association */
            /* Commented out to show relations from other docs
            if ($association->getLsDoc()->getId() !== $this->getLsDoc()->getId()) {
                continue;
            }
            */
            $assocName = LsAssociation::inverseName($association->getType());
            if (null === $assocName) {
                $assocName = 'Inverse '.$association->getType();
            }

            $groups[$assocName]->add($association);
        }

        return $groups;
    }

    /**
     * Get a representation of the item.
     */
    public function getDisplayIdentifier(): string
    {
        if (null !== $this->humanCodingScheme) {
            return $this->humanCodingScheme;
        }

        if (null !== $this->abbreviatedStatement) {
            return $this->abbreviatedStatement;
        }

        if ($this->fullStatement) {
            return $this->fullStatement;
        }

        $uri = $this->getUri();
        $uri = preg_replace('#^.*/#', '', $uri);
        $uri = preg_replace('#^local:#', '', $uri);

        return $uri;
    }

    /**
     * Get a short version of the statement.
     */
    public function getShortStatement(): string
    {
        if ($this->abbreviatedStatement) {
            return $this->getAbbreviatedStatement();
        }

        return mb_substr($this->getFullStatement(), 0, 60);
    }

    public function setLsDocUri(?string $lsDocUri): LsItem
    {
        $this->lsDocUri = $lsDocUri;

        return $this;
    }

    public function getLsDocUri(): ?string
    {
        return $this->lsDocUri;
    }

    public function setHumanCodingScheme(?string $humanCodingScheme): LsItem
    {
        $this->humanCodingScheme = $humanCodingScheme;

        return $this;
    }

    public function getHumanCodingScheme(): ?string
    {
        return $this->humanCodingScheme;
    }

    public function setListEnumInSource(?string $listEnumInSource): LsItem
    {
        $this->listEnumInSource = $listEnumInSource;

        return $this;
    }

    public function getListEnumInSource(): ?string
    {
        return $this->listEnumInSource;
    }

    public function setFullStatement(string $fullStatement): LsItem
    {
        $this->fullStatement = $fullStatement;

        return $this;
    }

    public function getFullStatement(): ?string
    {
        return $this->fullStatement;
    }

    public function setAbbreviatedStatement(?string $abbreviatedStatement): LsItem
    {
        $this->abbreviatedStatement = $abbreviatedStatement;

        return $this;
    }

    public function getAbbreviatedStatement(): ?string
    {
        return $this->abbreviatedStatement;
    }

    /**
     * @param string[]|null $conceptKeywords
     */
    public function setConceptKeywordsArray(?array $conceptKeywords): LsItem
    {
        if (null === $conceptKeywords) {
            $conceptKeywords = [];
        }

        if ([] !== array_filter($conceptKeywords, static function ($el) {
            return !\is_string($el);
        })) {
            throw new \InvalidArgumentException('setConceptKeywords must be passed an array of strings.');
        }

        $this->conceptKeywords = $conceptKeywords;

        return $this;
    }

    public function getConceptKeywordsArray(): array
    {
        return $this->conceptKeywords ?? [];
    }

    /**
     * @deprecated Migrate to using setConceptKeywordsArray()
     */
    public function setConceptKeywords(?string $conceptKeywords): LsItem
    {
        return $this->setConceptKeywordsString($conceptKeywords);
    }

    /**
     * @deprecated Migrate to using getConceptKeywordsArray()
     */
    public function getConceptKeywords(): ?string
    {
        return $this->getConceptKeywordsString();
    }

    public function setConceptKeywordsString(?string $conceptKeywords): LsItem
    {
        if (null === $conceptKeywords) {
            $conceptKeywords = '';
        }

        $values = preg_split('/ *, */', $conceptKeywords, -1, PREG_SPLIT_NO_EMPTY);

        $this->setConceptKeywordsArray($values);

        return $this;
    }

    public function getConceptKeywordsString(): ?string
    {
        return implode(',', $this->getConceptKeywordsArray());
    }

    /**
     * @deprecated Should use getConcepts() and use the set returned instead, this only gives the first
     */
    public function getConceptKeywordsUri(): ?string
    {
        $concepts = $this->getConcepts();

        if ($concepts->isEmpty()) {
            return null;
        }

        return $concepts->first()->getUri();
    }

    public function setNotes(?string $notes): LsItem
    {
        $this->notes = $notes;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @param string|string[]|null $educationalAlignment
     */
    public function setEducationalAlignment($educationalAlignment): LsItem
    {
        if (null === $educationalAlignment) {
            $this->educationalAlignment = null;

            return $this;
        }

        if (is_string($educationalAlignment)) {
            $this->educationalAlignment = $educationalAlignment;

            return $this;
        }

        if (!is_array($educationalAlignment)) {
            throw new \InvalidArgumentException('setEducationalAlignment must be passed a string or an array of strings.');
        }

        if ([] !== array_filter($educationalAlignment, static function ($el) {
            return !is_string($el);
        })) {
            throw new \InvalidArgumentException('setEducationalAlignment must be passed a string or an array of strings.');
        }

        $this->educationalAlignment = implode(',', $educationalAlignment);

        return $this;
    }

    public function getEducationalAlignment(): ?string
    {
        return $this->educationalAlignment;
    }

    public function getType(): ?string
    {
        $itemType = $this->itemType;
        if (null !== $itemType) {
            return $itemType->getTitle();
        }

        return null;
    }

    /**
     * Add an item as a child this item.
     *
     * @throws \UnexpectedValueException
     */
    public function addChild(LsItem $child, ?LsDefAssociationGrouping $assocGroup = null, ?int $sequenceNumber = null): LsItem
    {
        $association = new LsAssociation();
        $association->setLsDoc($child->getLsDoc());
        $association->setOrigin($child);
        $association->setType(LsAssociation::CHILD_OF);
        $association->setDestination($this);

        if (null !== $sequenceNumber) {
            $association->setSequenceNumber($sequenceNumber);
        }

        if (null !== $assocGroup) {
            $association->setGroup($assocGroup);
        }

        $child->addAssociation($association);
        $this->addInverseAssociation($association);

        return $this;
    }

    /**
     * @return Collection|LsItem[]
     */
    public function getChildren(): Collection
    {
        $children = new ArrayCollection();

        $associations = $this->getInverseAssociations();
        foreach ($associations as $association) {
            /** @var LsAssociation $association */
            if (LsAssociation::CHILD_OF === $association->getType()) {
                $children->add($association->getOriginLsItem());
            }
        }

        return $children;
    }

    /**
     * @return array|int[]
     */
    public function getChildIds(): array
    {
        $ids = $this->getChildren()->map(
            static function (LsItem $item) {
                return $item->getId();
            }
        );

        return $ids->toArray();
    }

    /**
     * Find all children items of this item.
     */
    public function getDescendantIds(): array
    {
        $childIds = [];
        $hasChildren = $this->getChildren();
        foreach ($hasChildren as $child) {
            $id = $child->getId();
            $childIds[$id] = $id;
            $childIds = array_merge($childIds, $child->getDescendantIds());
        }

        return $childIds;
    }

    public function setLsDoc(LsDoc $lsDoc): LsItem
    {
        $this->lsDoc = $lsDoc;
        $this->lsDocUri = $lsDoc->getUri();
        $this->lsDocIdentifier = $lsDoc->getIdentifier();

        return $this;
    }

    public function getLsDoc(): LsDoc
    {
        return $this->lsDoc;
    }

    /**
     * @return Collection|LsItem[]
     */
    public function getLsItemParent(): Collection
    {
        $parents = new ArrayCollection();
        $associations = $this->getAssociations();
        foreach ($associations as $association) {
            /** @var LsAssociation $association */
            if (LsAssociation::CHILD_OF === $association->getType()
                && null !== $association->getDestinationLsItem()
            ) {
                $parents->add($association->getDestinationLsItem());
            }
        }

        return $parents;
    }

    public function addAssociation(LsAssociation $association): LsItem
    {
        $this->associations[] = $association;

        return $this;
    }

    public function removeAssociation(LsAssociation $association): LsItem
    {
        $this->associations->removeElement($association);

        return $this;
    }

    /**
     * @return Collection|LsAssociation[]
     */
    public function getAssociations(): Collection
    {
        return $this->associations;
    }

    public function addInverseAssociation(LsAssociation $inverseAssociation): LsItem
    {
        $this->inverseAssociations[] = $inverseAssociation;

        return $this;
    }

    public function removeInverseAssociation(LsAssociation $inverseAssociation): LsItem
    {
        $this->inverseAssociations->removeElement($inverseAssociation);

        return $this;
    }

    /**
     * @return Collection|LsAssociation[]
     */
    public function getInverseAssociations(): Collection
    {
        return $this->inverseAssociations;
    }

    public function getTopItemOf(): Collection
    {
        $topItemOf = new ArrayCollection();

        $associations = $this->getAssociations();
        foreach ($associations as $association) {
            /** @var LsAssociation $association */
            if (LsAssociation::CHILD_OF === $association->getType()
                && null !== $association->getDestinationLsDoc()
            ) {
                $topItemOf->add($association->getDestinationLsDoc());
            }
        }

        return $topItemOf;
    }

    public function getParentItem(): ?LsItem
    {
        $first = $this->getLsItemParent()->first();
        if ($first) {
            return $first;
        }

        return null;
    }

    public function getLsDocIdentifier(): ?string
    {
        return $this->lsDocIdentifier;
    }

    public function setLsDocIdentifier(?string $lsDocIdentifier): LsItem
    {
        $this->lsDocIdentifier = $lsDocIdentifier;

        return $this;
    }

    /**
     * @param LsItem|LsDoc $parent
     *
     * @throws \UnexpectedValueException
     */
    public function addParent($parent, ?int $sequenceNumber = null, ?LsDefAssociationGrouping $assocGroup = null): LsAssociation
    {
        $association = new LsAssociation();
        $association->setLsDoc($this->getLsDoc());
        $association->setOrigin($this);
        $association->setType(LsAssociation::CHILD_OF);
        $association->setDestination($parent ?: $this->lsDoc);

        // set sequenceNumber if provided
        if (null !== $sequenceNumber) {
            $association->setSequenceNumber($sequenceNumber);
        }

        // set assocGroup if provided
        if (null !== $assocGroup) {
            $association->setGroup($assocGroup);
        }

        $this->addAssociation($association);
        $parent->addInverseAssociation($association);

        return $association;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): LsItem
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get (an indented) label representing this item.
     */
    public function getLabel(string $indent = "\u{00a0}\u{00a0}\u{00a0}\u{00a0}"): string
    {
        $pfx = '';
        $parent = $this->getLsItemParent();
        while (!$parent->isEmpty()) {
            $pfx .= $indent;
            $parent = $parent->current()->getLsItemParent();
        }
        $statement = $this->getShortStatement();
        $code = $this->getHumanCodingScheme();
        if (null !== $code && '' !== $code) {
            $code .= ' - ';
        }

        return "{$pfx}{$code}{$statement}";
    }

    /**
     * Determine if the LsItem is editable.
     */
    public function canEdit(): bool
    {
        return $this->lsDoc->canEdit();
    }

    public function getItemType(): ?LsDefItemType
    {
        return $this->itemType;
    }

    public function setItemType(?LsDefItemType $itemType): LsItem
    {
        $this->itemType = $itemType;

        return $this;
    }

    public function getItemTypeText(): ?string
    {
        return $this->itemTypeText;
    }

    public function setItemTypeText(?string $itemTypeText): LsItem
    {
        $this->itemTypeText = $itemTypeText;

        return $this;
    }

    /**
     * @return LsDefConcept[]|Collection
     */
    public function getConcepts()
    {
        return $this->concepts;
    }

    /**
     * @param LsDefConcept[]|Collection|null $concepts
     *
     * @return LsItem
     */
    public function setConcepts(?iterable $concepts): LsItem
    {
        $this->concepts = new ArrayCollection();

        if (null === $concepts) {
            return $this;
        }

        foreach ($concepts as $concept) {
            $this->addConcept($concept);
        }

        return $this;
    }

    public function addConcept(LsDefConcept $concept): LsItem
    {
        $this->concepts[] = $concept;

        return $this;
    }

    public function getAlternativeLabel(): ?string
    {
        return $this->alternativeLabel;
    }

    public function setAlternativeLabel(?string $alternativeLabel): LsItem
    {
        $this->alternativeLabel = $alternativeLabel;

        return $this;
    }

    public function getStatusStart(): ?\DateTimeInterface
    {
        if (null === $this->statusStart) {
            return $this->lsDoc->getStatusStart();
        }

        return $this->statusStart;
    }

    public function setStatusStart(?\DateTimeInterface $statusStart): LsItem
    {
        $this->statusStart = $statusStart;

        return $this;
    }

    public function getStatusEnd(): ?\DateTimeInterface
    {
        if (null === $this->statusEnd) {
            return $this->lsDoc->getStatusEnd();
        }

        return $this->statusEnd;
    }

    public function setStatusEnd(?\DateTimeInterface $statusEnd): LsItem
    {
        $this->statusEnd = $statusEnd;

        return $this;
    }

    public function getLicence(): ?LsDefLicence
    {
        return $this->licence;
    }

    public function setLicence(?LsDefLicence $licence): LsItem
    {
        $this->licence = $licence;

        return $this;
    }

    /**
     * @return CfRubricCriterion[]|Collection
     */
    public function getCriteria(): Collection
    {
        return $this->criteria;
    }

    public function addCriterion(CfRubricCriterion $criterion): LsItem
    {
        $this->criteria[] = $criterion;

        return $this;
    }

    /**
     * @param CfRubricCriterion[]|Collection|null $criteria
     */
    public function setCriteria(?iterable $criteria): LsItem
    {
        $this->criteria = new ArrayCollection();

        if (null === $criteria) {
            return $this;
        }

        foreach ($criteria as $criterion) {
            $this->addCriterion($criterion);
        }

        return $this;
    }
}
