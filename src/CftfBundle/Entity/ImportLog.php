<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ImportLog
 *
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\ImportLogRepository")
 * @ORM\Table(name="import_logs")
 * @UniqueEntity("id")
 */
class ImportLog
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
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc", inversedBy="importLogs")
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
     * @ORM\Column(name="message_type", type="string", nullable=false)
     */
    protected $messageType;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_read", type="boolean", nullable=false)
     */
    protected $read = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->messageType = 'warning';
    }

    /**
     * Set LsDoc
     *
     * @param LsDoc $lsDoc
     *
     * @return ImportLog
     */
    public function setLsDoc($lsDoc) {
        $this->lsDoc = $lsDoc;

        return $this;
    }

    /**
     * Set message
     *
     * @param string $message
     *
     * @return ImportLog
     */
    public function setMessage($message) {
        $this->message = $message;

        return $this;
    }

    /**
     * Get read
     *
     * @return bool
     */
    public function getRead(){
        return $this->read;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage(){
        return $this->message;
    }

    /**
     * Set read as true
     *
     * @return ImportLog
     */
    public function markAsRead(){
        $this->read = true;

        return $this;
    }

    /**
     * Get messageType
     *
     * @return string
     */
    public function getMessageType(){
        return $this->messageType;
    }

    /**
     * Set messageType
     *
     * @param string $messageType
     *
     * @return ImportLog
     */
    public function setMessageType($messageType){
        $this->messageType = $messageType;

         return $this;
    }
}
