<?php
namespace App\DTO;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Framework\LsDoc;

class LsItemAdditionalFieldFormObject {
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
    public $language;

    /**
     * @var string
     * @Assert\Length(max=20)
     */
    public $educationalAlignment;

    /**
     * @var string
     * @Assert\Length(max=20)
     */
    public $itemType;

    /**
     * @var string
     * @Assert\Length(max=20)
     */
    public $licenceUri;

    /**
     * @var string
     * @Assert\Length(max=20)
     */
    public $notes;

    /**
     * Set lsDoc
     *
     * @param LsDoc $lsDoc
     *
     * @return LsItem
     */
    public function setLsDoc(LsDoc $lsDoc): LsItemAdditionalFieldFormObject
    {
        $this->lsDoc = $lsDoc;
        $this->lsDocUri = $lsDoc->getUri();
        $this->lsDocIdentifier = $lsDoc->getIdentifier();

        return $this;
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
}
