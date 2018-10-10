<?php
namespace App\DTO;
use Symfony\Component\Validator\Constraints as Assert;

class ChangeLsItemData {
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
}
