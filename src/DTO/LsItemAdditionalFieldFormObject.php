<?php
namespace App\DTO;
use App\Entity\Framework\LsItem;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Framework\LsDoc;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\ORM\Mapping as ORM;

class LsItemAdditionalFieldFormObject
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     *
     * @Serializer\Exclude()
     */
    private $lsDocIdentifier;

    /**
     * @var string
     * @ORM\Column(name="ls_doc_uri", type="string", length=300, nullable=true)
     * @Assert\Length(max=300)
     */
    private $lsDocUri;

    /**
     * @var LsDoc
     * @ORM\ManyToOne(targetEntity="LsDoc", inversedBy="lsItems")
     * @Assert\NotBlank()
     */
    private $lsDoc;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $fullStatement;

    /**
     * @var string
     * @Assert\Length(max=50)
     */
    public $humanCodingScheme;

    /**
     * @var string
     * @Assert\Length(max=20)
     */
    public $listEnumInSource;

    /**
     * @var string
     * @Assert\Length(max=60)
     */
    public $abbreviatedStatement;

    /**
     * @var string
     * @Assert\Length(max=300)
     */
    public $conceptKeywords;

    /**
     * @var string
     * @Assert\Length(max=300)
     */
    public $conceptKeywordsUri;

    /**
     * @var string
     * @Assert\Length(max=20)
     */
    public $licenceUri;

    /**
     * @var string
     * @Assert\Length(max=600)
     */
    public $notes;

    public $additionalFields = [];

    /**
     * @var LsItem
     */
    private $lsItem;

    /**
     * Set lsDoc
     *
     * @param LsDoc $lsDoc
     *
     * @return LsItemAdditionalFieldFormObject
     */
    public function setLsDoc(LsDoc $lsDoc): LsItemAdditionalFieldFormObject
    {
        $this->lsDoc = $lsDoc;
        $this->lsDocUri = $lsDoc->getUri();
        $this->lsDocIdentifier = $lsDoc->getIdentifier();

        return $this;
    }

    /**
     * Get lsDoc
     *
     * @return LsDoc
     */
    public function getLsDoc(): LsDoc
    {
        return $this->lsDoc;
    }

    /**
     * Set lsDocUri
     *
     * @param string $lsDocUri
     *
     * @return LsItem
     */
    public function setLsDocUri(?string $lsDocUri): LsItemAdditionalFieldFormObject
    {
        $this->lsDocUri = $lsDocUri;

        return $this;
    }

    /**
     * Get lsDocUri
     *
     * @return string
     */
    public function getLsDocUri(): ?string
    {
        return $this->lsDocUri;
    }

    /**
     * @return string
     */
    public function getLsDocIdentifier(): string
    {
        return $this->lsDocIdentifier;
    }

    /**
     * @return string
     */
    public function getFullStatement(): ?string
    {
        return $this->fullStatement;
    }

    /**
     * @return string
     */
    public function getHumanCodingScheme(): ?string
    {
        return $this->humanCodingScheme;
    }

    /**
     * @return string
     */
    public function getListEnumInSource(): ?string
    {
        return $this->listEnumInSource;
    }

    /**
     * @return string
     */
    public function getAbbreviatedStatement(): ?string
    {
        return $this->abbreviatedStatement;
    }

    /**
     * @return string
     */
    public function getConceptKeywords(): ?string
    {
        return $this->conceptKeywords;
    }

    /**
     * @return string
     */
    public function getConceptKeywordsUri(): ?string
    {
        return $this->conceptKeywordsUri;
    }

    /**
     * @return string
     */
    public function getLicenceUri(): ?string
    {
        return $this->licenceUri;
    }

    /**
     * @return string
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @return array
     */
    public function getAdditionalFields(): array
    {
        return $this->additionalFields;
    }

    public function __set(string $name, $value)
    {
        $this->additionalFields[$name] = $value;
    }

    public function __get(string $name)
    {
        if (!isset($this->additionalFields[$name])) {
            return null;
        }

        return $this->additionalFields[$name];
    }

    public function lsItem(): LsItem
    {
        if (null === $this->lsItem) {
          $this->lsItem = $this->lsDoc->createItem();
        }

        $this->lsItem->setFullStatement($this->getFullStatement());
        $this->lsItem->setAbbreviatedStatement($this->getAbbreviatedStatement());
        $this->lsItem->setListEnumInSource($this->getListEnumInSource());
        $this->lsItem->setConceptKeywords($this->getConceptKeywords());
        $this->lsItem->setConceptKeywordsUri($this->getConceptKeywordsUri());
        $this->lsItem->setLicenceUri($this->getLicenceUri());
        $this->lsItem->setNotes($this->getNotes());
        $this->lsItem->setExtra(['customFields' => $this->getAdditionalFields()]);

        return $this->lsItem;

    }

    public static function fromlsItem(LsItem $lsItem): self
    {  
        $item = new self();

        $item->fullStatement = $lsItem->getFullStatement();
        $item->humanCodingScheme = $lsItem->getHumanCodingScheme();
        $item->listEnumInSource = $lsItem->getListEnumInSource();
        $item->abbreviatedStatement = $lsItem->getAbbreviatedStatement();
        $item->conceptKeywords = $lsItem->getConceptKeywords();
        $item->conceptKeywordsUri = $lsItem->getConceptKeywordsUri();
        $item->licenceUri = $lsItem->getLicenceUri();
        $item->notes = $lsItem->getNotes();
        $item->additionalFields = $lsItem->getExtra()['customFields'];
        $item->lsDocIdentifier = $lsItem->getLsDocIdentifier();
        $item->lsDocUri = $lsItem->getLsDocUri();
        $item->lsDoc = $lsItem->getLsDoc();
        $item->lsItem = $lsItem;

        return $item;
    }

}
