<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AwsStorage
 *
 * @ORM\Entity(repositoryClass="App\Repository\Framework\AwsStorageRepository")
 * @ORM\Table(name="aws_storage")
 * @UniqueEntity("id")
 */
class AwsStorage
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="LsDoc", inversedBy="awsStorage")
     * @ORM\JoinColumn(name="ls_doc_id", referencedColumnName="id", nullable=false)
     *
     * @Assert\NotBlank()
     */
    protected $lsDoc;

    /**
     * @var LsItem
     *
     * @ORM\ManyToOne(targetEntity="LsItem", inversedBy="awsStorage")
     * @ORM\JoinColumn(name="ls_item_id", referencedColumnName="id", nullable=true)
     *
     * @Assert\NotBlank
     */
    protected $lsItem;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", length=250)
     *
     * @Assert\NotBlank
     */
    protected $fileName;

    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * Get the internal id of the object (or null if not persisted)
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set LsDoc
     *
     * @param LsDoc $lsDoc
     *
     * @return AwsStorage
     */
    public function setLsDoc($lsDoc): AwsStorage
    {
        $this->lsDoc = $lsDoc;

        return $this;
    }

    /**
     * Set LsItem
     *
     * @param LsItem $lsItem
     *
     * @return AwsStorage
     */
    public function setLsItem($lsItem): AwsStorage
    {
        $this->lsItem = $lsItem;

        return $this;
    }

    /**
     * Set fileName
     *
     * @param string $fileName
     *
     * @return AwsStorage
     */
    public function setFileName($fileName): AwsStorage
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get lsDoc
     *
     * @return LsDoc
     */
    public function getLsDoc(): ?LsDoc
    {
        return $this->lsDoc;
    }

    /**
     * Get lsItem
     *
     * @return LsItem
     */
    public function getLsItem(): ?LsItem
    {
        return $this->lsItem;
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }
}