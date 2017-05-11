<?php

namespace Salt\UserBundle\Entity;

use CftfBundle\Entity\LsDoc;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Organization
 *
 * @ORM\Entity(repositoryClass="Salt\UserBundle\Repository\ImportationLogRepository")
 * @ORM\Table(name="importation_logs")
 * @UniqueEntity("id")
 */
class ImportationLog
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
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc", inversedBy="lsDoc")
     * @ORM\JoinColumn(name="ls_doc_id", referencedColumnName="id", nullable=false)
     *
     * @Assert\NotBlank()
     */
    protected $lsDoc;

    /**
     * @var string
     *
     * @ORM\Column(name="message_text", type="string", length=250)
     *
     * @Assert\NotBlank
     */
    protected $message;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", nullable=false)
     */
    protected $type = '';

    /**
     * @var bool
     *
     * @ORM\Column(name="is_read", type="boolean", nullable=false)
     */
    protected $read = false;

    /**
     * @param CftfBundle\Entity\LsDoc $lsDoc
     *
     * @return User
     */
    public function setLsDoc($lsDoc) {
        $this->lsDoc = $lsDoc;

        return $this;
    }

    public function setMessage($message) {
        $this->message = $message;

        return $this;
    }

    public function getRead(){
        return $this->read;
    }

    public function getMessage(){
        return $this->message;
    }

    public function markAsRead(){
        $this->read = true;

        return $this;
    }

    public function getType(){
        return $this->type;
    }

    public function setType($newType){
        $this->type = $newType;

         return $this;
    }
}

