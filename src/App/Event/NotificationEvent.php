<?php

namespace App\Event;

use CftfBundle\Entity\LsDoc;
use Symfony\Component\EventDispatcher\Event;

class NotificationEvent extends Event
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var LsDoc
     */
    private $doc;

    /**
     * @var array
     */
    private $changed;

    public function __construct(string $message, ?LsDoc $doc, array $changed = [])
    {
        $this->message = $message;
        $this->doc = $doc;
        $this->changed = $changed;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDoc(): ?LsDoc
    {
        return $this->doc;
    }

    public function getChanged(): array
    {
        return $this->changed;
    }
}
