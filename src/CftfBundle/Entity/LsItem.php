<?php

namespace CftfBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * LsItem
 *
 * @ORM\Table(name="ls_item")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\LsItemRepository")
 * @UniqueEntity("uri")
 */
class LsItem
{
    const DISPLAY_IDENTIFIER_MAXLENGTH = 32;

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
     * @ORM\Column(name="uri", type="string", length=300, nullable=true, unique=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     */
    private $uri;

    /**
     * @var string
     *
     * @ORM\Column(name="ls_doc_identifier", type="string", length=300, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     */
    private $lsDocIdentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="ls_doc_uri", type="string", length=300, nullable=true)
     * @Assert\Length(max=300)
     */
    private $lsDocUri;

    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc", inversedBy="lsItems")
     * @Assert\NotBlank()
     */
    private $lsDoc;

    /**
     * @var string
     *
     * @ORM\Column(name="human_coding_scheme", type="string", length=50, nullable=true)
     *
     * @Assert\Length(max=50)
     */
    private $humanCodingScheme;

    /**
     * @var string
     *
     * @ORM\Column(name="identifier", type="string", length=300, nullable=false, unique=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=50)
     */
    private $identifier;

    /**
     * @var string
     *
     * @ORM\Column(name="list_enum_in_source", type="string", length=10, nullable=true)
     *
     * @Assert\Length(max=10)
     */
    private $listEnumInSource;

    /**
     * @var int
     *
     * @ORM\Column(name="rank", type="bigint", nullable=true)
     */
    private $rank;

    /**
     * @var string
     *
     * @ORM\Column(name="full_statement", type="text", nullable=false)
     *
     * @Assert\NotBlank()
     */
    private $fullStatement;

    /**
     * @var string
     *
     * @ORM\Column(name="abbreviated_statement", type="string", length=50, nullable=true)
     *
     * @Assert\Length(max=50)
     */
    private $abbreviatedStatement;

    /**
     * @var string
     *
     * @ORM\Column(name="concept_keywords", type="string", length=300, nullable=true)
     *
     * @Assert\Length(max=300)
     */
    private $conceptKeywords;

    /**
     * @var string
     *
     * @ORM\Column(name="concept_keywords_uri", type="string", length=300, nullable=true)
     *
     * @Assert\Length(max=300)
     * @Assert\Url()
     */
    private $conceptKeywordsUri;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    private $notes;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=10, nullable=true)
     *
     * @Assert\Length(max=10)
     */
    private $language;

    /**
     * @var string
     *
     * @ORM\Column(name="educational_alignment", type="string", length=300, nullable=true)
     *
     * @Assert\Length(max=300)
     */
    private $educationalAlignment;

    /**
     * @var LsDefItemType
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDefItemType")
     * @ORM\JoinColumn(name="item_type_id", referencedColumnName="id")
     */
    private $itemType;

    /**
     * @var string
     *
     * @ORM\Column(name="licence_uri", type="string", length=300, nullable=true)
     *
     * @Assert\Length(max=300)
     * @Assert\Url()
     */
    private $licenceUri;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="changed_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     *
     * @Assert\DateTime()
     */
    private $changedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", columnDefinition="DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @var array
     *
     * @ORM\Column(name="extra", type="json_array", nullable=true)
     */
    private $extra;

    /**
     * @var Collection|LsAssociation[]
     *
     * @ORM\OneToMany(targetEntity="CftfBundle\Entity\LsAssociation", mappedBy="originLsItem", indexBy="id", cascade={"persist"})
     */
    private $associations;

    /**
     * @var Collection|LsAssociation[]
     *
     * @ORM\OneToMany(targetEntity="CftfBundle\Entity\LsAssociation", mappedBy="destinationLsItem", indexBy="id", cascade={"persist"})
     */
    private $inverseAssociations;


    /**
     * LsItem constructor.
     *
     * @param string|Uuid|null $identifier
     */
    public function __construct($identifier = null)
    {
        if (null !== $identifier) {
            // If the identifier is in the form of a UUID then lower case it
            if ($identifier instanceof Uuid) {
                $identifier = strtolower($identifier->toString());
            } elseif (is_string($identifier) && Uuid::isValid($identifier)) {
                $identifier = strtolower(Uuid::fromString($identifier)->toString());
            } else {
                $identifier = Uuid::uuid4()->toString();
            }
        } else {
            $identifier = Uuid::uuid4()->toString();
        }

        $this->identifier = $identifier;
        $this->uri = 'local:'.$this->identifier;
        $this->children = new ArrayCollection();
        $this->lsItemParent = new ArrayCollection();
        $this->associations = new ArrayCollection();
        $this->inverseAssociations = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->uri;
    }

    /**
     * Create a copy of the lsItem into a new document
     *
     * @param LsDoc $newLsDoc
     *
     * @return LsItem
     */
    public function copyToLsDoc(LsDoc $newLsDoc)
    {
        $newItem = new LsItem();

        $newItem->setLsDoc($newLsDoc);
        if (null !== $this->abbreviatedStatement) {
            $newItem->setAbbreviatedStatement($this->abbreviatedStatement);
        }
        $newItem->setFullStatement($this->getFullStatement());
        if (null !== $this->humanCodingScheme) {
            $newItem->setHumanCodingScheme($this->humanCodingScheme);
        }
        if (null !== $this->notes) {
            $newItem->setNotes($this->notes);
        }
        if (null !== $this->itemType) {
            $newItem->setItemType($this->itemType);
        }
        if (null !== $this->language) {
            $newItem->setLanguage($this->language);
        }
        if (null !== $this->educationalAlignment) {
            $newItem->setEducationalAlignment($this->educationalAlignment);
        }
        if (null !== $this->extra) {
            $newItem->setExtra($this->extra);
        }
        if (null !== $this->conceptKeywords) {
            $newItem->setConceptKeywords($this->conceptKeywords);
        }
        if (null !== $this->conceptKeywordsUri) {
            $newItem->setConceptKeywordsUri($this->conceptKeywordsUri);
        }
        if (null !== $this->licenceUri) {
            $newItem->setLicenceUri($this->licenceUri);
        }

        // Add an "Exact" relationship to the original
        $exactMatch = new LsAssociation();
        $exactMatch->setLsDoc($newLsDoc);
        $exactMatch->setOrigin($newItem);
        $exactMatch->setType(LsAssociation::EXACT_MATCH_OF);
        $exactMatch->setDestination($this);
        $newItem->addAssociation($exactMatch);
        $this->addInverseAssociation($exactMatch);

        foreach ($this->getChildren() as $child) {
            $newChild = $child->copyToLsDoc($newLsDoc);
            $newItem->addChild($newChild);
        }

        return $newItem;
    }

    public function isLsItem()
    {
        return true;
    }

    public function getGroupedAssociations()
    {
        /** @var Collection $groups[] */
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
            if (empty($assocName)) {
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
            if ($association->getLsDoc()->getId() !== $this->getLsDoc()->getId()) {
                continue;
            }
            $assocName = LsAssociation::inverseName($association->getType());
            if (empty($assocName)) {
                $assocName = 'Inverse '.$association->getType();
            }

            $groups[$assocName]->add($association);
        }

        return $groups;
    }

    public function getDisplayIdentifier()
    {
        if ($this->humanCodingScheme) {
            return $this->getHumanCodingScheme();
        }

        if ($this->abbreviatedStatement) {
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

    public function getShortStatement()
    {
        if ($this->abbreviatedStatement) {
            return $this->getAbbreviatedStatement();
        }

        $statement = substr($this->getFullStatement(), 0, 50);

        return $statement;
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
     * @return LsItem
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
     * Set lsDocUri
     *
     * @param string $lsDocUri
     *
     * @return LsItem
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
     * Set humanCodingScheme
     *
     * @param string $humanCodingScheme
     *
     * @return LsItem
     */
    public function setHumanCodingScheme($humanCodingScheme)
    {
        $this->humanCodingScheme = $humanCodingScheme;

        return $this;
    }

    /**
     * Get humanCodingScheme
     *
     * @return string
     */
    public function getHumanCodingScheme()
    {
        return $this->humanCodingScheme;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     *
     * @return LsItem
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
     * Set listEnumInSource
     *
     * @param string $listEnumInSource
     *
     * @return LsItem
     */
    public function setListEnumInSource($listEnumInSource)
    {
        $this->listEnumInSource = $listEnumInSource;

        return $this;
    }

    /**
     * Get listEnumInSource
     *
     * @return string
     */
    public function getListEnumInSource()
    {
        return $this->listEnumInSource;
    }

    /**
     * Set fullStatement
     *
     * @param string $fullStatement
     *
     * @return LsItem
     */
    public function setFullStatement($fullStatement)
    {
        $this->fullStatement = $fullStatement;

        return $this;
    }

    /**
     * Get fullStatement
     *
     * @return string
     */
    public function getFullStatement()
    {
        return $this->fullStatement;
    }

    /**
     * Set abbreviatedStatement
     *
     * @param string $abbreviatedStatement
     *
     * @return LsItem
     */
    public function setAbbreviatedStatement($abbreviatedStatement)
    {
        $this->abbreviatedStatement = $abbreviatedStatement;

        return $this;
    }

    /**
     * Get abbreviatedStatement
     *
     * @return string
     */
    public function getAbbreviatedStatement()
    {
        return $this->abbreviatedStatement;
    }

    /**
     * Set conceptKeywords
     *
     * @param string $conceptKeywords
     *
     * @return LsItem
     */
    public function setConceptKeywords($conceptKeywords)
    {
        $this->conceptKeywords = $conceptKeywords;

        return $this;
    }

    /**
     * Get conceptKeywords
     *
     * @return string
     */
    public function getConceptKeywords()
    {
        return $this->conceptKeywords;
    }

    /**
     * Set conceptKeywordsUri
     *
     * @param string $conceptKeywordsUri
     *
     * @return LsItem
     */
    public function setConceptKeywordsUri($conceptKeywordsUri)
    {
        $this->conceptKeywordsUri = $conceptKeywordsUri;

        return $this;
    }

    /**
     * Get conceptKeywordsUri
     *
     * @return string
     */
    public function getConceptKeywordsUri()
    {
        return $this->conceptKeywordsUri;
    }

    /**
     * Set notes
     *
     * @param string $notes
     *
     * @return LsItem
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set educationalAlignment
     *
     * @param string $educationalAlignment
     *
     * @return LsItem
     */
    public function setEducationalAlignment($educationalAlignment)
    {
        $this->educationalAlignment = $educationalAlignment;

        return $this;
    }

    /**
     * Get educationalAlignment
     *
     * @return string
     */
    public function getEducationalAlignment()
    {
        return $this->educationalAlignment;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        $itemType = $this->itemType;
        if (null !== $itemType) {
            return $itemType->getTitle();
        }

        return null;
    }

    /**
     * Set licenceUri
     *
     * @param string $licenceUri
     *
     * @return LsItem
     */
    public function setLicenceUri($licenceUri)
    {
        $this->licenceUri = $licenceUri;

        return $this;
    }

    /**
     * Get licenceUri
     *
     * @return string
     */
    public function getLicenceUri()
    {
        return $this->licenceUri;
    }

    /**
     * Set changedAt
     *
     * @param \DateTime $changedAt
     *
     * @return LsItem
     */
    public function setChangedAt($changedAt)
    {
        $this->changedAt = $changedAt;

        return $this;
    }

    /**
     * Get changedAt
     *
     * @return \DateTime
     */
    public function getChangedAt()
    {
        return $this->changedAt;
    }

    /**
     * Add child
     *
     * @param LsItem $child
     *
     * @return LsItem
     */
    public function addChild(LsItem $child)
    {
        $association = new LsAssociation();
        $association->setLsDoc($child->getLsDoc());
        $association->setOrigin($child);
        $association->setType(LsAssociation::CHILD_OF);
        $association->setDestination($this);
        $child->addAssociation($association);
        $this->addInverseAssociation($association);

        return $this;
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection|LsItem[]
     */
    public function getChildren()
    {
        $children = new ArrayCollection();

        $associations = $this->getInverseAssociations();
        foreach ($associations as $association) {
            /** @var LsAssociation $association */
            if ($association->getType() === LsAssociation::CHILD_OF) {
                $children->add($association->getOriginLsItem());
            }
        }

        return $children;
    }

    /**
     * Get children ids
     *
     * @return array|int[]
     */
    public function getChildIds()
    {
        $ids = $this->getChildren()->map(function (LsItem $item) {
            return $item->getId();
        });

        return $ids->toArray();
    }

    /**
     * Set lsDoc
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     *
     * @return LsItem
     */
    public function setLsDoc(\CftfBundle\Entity\LsDoc $lsDoc = null)
    {
        $this->lsDoc = $lsDoc;
        $this->lsDocUri = $lsDoc->getUri();
        $this->lsDocIdentifier = $lsDoc->getIdentifier();

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
     * Get lsItemParent
     *
     * @return \Doctrine\Common\Collections\Collection|LsItem[]
     */
    public function getLsItemParent()
    {
        $parents = new ArrayCollection();
        $associations = $this->getAssociations();
        foreach ($associations as $association) {
            /** @var LsAssociation $association */
            if ($association->getType() === LsAssociation::CHILD_OF
                && $association->getDestinationLsItem() !== null) {
                $parents->add($association->getDestinationLsItem());
            }
        }

        return $parents;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return LsItem
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        $this->lsDoc->setUpdatedAt($updatedAt);

        $parents = $this->getLsItemParent();
        foreach ($parents as $parent){
            $parent->setUpdatedAt($updatedAt);
        }

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
     * @return LsItem
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
     * @return LsItem
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
     * Get topItemOf
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTopItemOf()
    {
        $topItemOf = new ArrayCollection();

        $associations = $this->getAssociations();
        foreach ($associations as $association) {
            /** @var LsAssociation $association */
            if ($association->getType() === LsAssociation::CHILD_OF
                && $association->getDestinationLsDoc() !== null) {
                $topItemOf->add($association->getDestinationLsDoc());
            }
        }

        return $topItemOf;
    }

    /**
     * @return LsItem|null
     */
    public function getParentItem()
    {
        $lsItem = $this->getLsItemParent()->first();

        return $lsItem;
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
     * @return LsItem
     */
    public function setLsDocIdentifier($lsDocIdentifier) {
        $this->lsDocIdentifier = $lsDocIdentifier;
        return $this;
    }

    /**
     * @return int
     */
    public function getRank() {
        return $this->rank;
    }

    /**
     * @param int $rank
     *
     * @return LsItem
     */
    public function setRank($rank) {
        $this->rank = $rank;
        return $this;
    }

    /**
     * @return array
     */
    public function getExtra() {
        return $this->extra;
    }

    /**
     * @param string $property
     * @param string $default
     *
     * @return mixed
     */
    public function getExtraProperty($property, $default = null) {
        if (is_null($this->extra)) {
            return $default;
        }

        if (!array_key_exists($property, $this->extra)) {
            return $default;
        }

        return $this->extra[$property];
    }

    /**
     * @param array $extra
     *
     * @return LsItem
     */
    public function setExtra($extra) {
        $this->extra = $extra;
        return $this;
    }

    /**
     * @param string $property
     * @param mixed $value
     *
     * @return LsItem
     */
    public function setExtraProperty($property, $value) {
        if (is_null($this->extra)) {
            $this->extra = [];
        }

        $this->extra[$property] = $value;
        return $this;
    }

    /**
     * Add Parent
     *
     * @param LsItem|LsDoc $parent
     *
     * @return LsItem
     */
    public function addParent($parent)
    {
        $association = new LsAssociation();
        $association->setLsDoc($this->getLsDoc());
        $association->setOrigin($this);
        $association->setType(LsAssociation::CHILD_OF);
        $association->setDestination($parent?:$this->lsDoc);
        $this->addAssociation($association);

        return $this;
    }

    /**
     * Get the LsItem language
     *
     * @return string
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * Set the LsItem language
     *
     * @param string $language
     *
     * @return LsItem
     */
    public function setLanguage($language) {
        $this->language = $language;
        return $this;
    }

    /**
     * Get (an indented) label representing this item
     *
     * @param string $indent
     *
     * @return string
     */
    public function getLabel($indent = "\u{00a0}\u{00a0}\u{00a0}\u{00a0}") {
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
     * Determine if the LsItem is editable
     *
     * @return bool
     */
    public function canEdit() {
        return $this->lsDoc->canEdit();
    }

    /**
     * @return LsDefItemType
     */
    public function getItemType() {
        return $this->itemType;
    }

    /**
     * @param LsDefItemType $itemType
     *
     * @return LsItem
     */
    public function setItemType($itemType) {
        $this->itemType = $itemType;
        return $this;
    }
}
