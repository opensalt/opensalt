<?php
namespace App\DTO;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Framework\LsDoc;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\LockableInterface;
use App\Entity\Framework\AbstractLsBase;
use App\Entity\Framework\CaseApiInterface;

class LsItemAdditionalFieldFormObject extends AbstractLsBase implements CaseApiInterface, LockableInterface {
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

    public $additionalFields = [];

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
}
