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
     * Get fileName
     *
     * @return string
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }

}
