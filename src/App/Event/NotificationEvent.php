<?php

namespace App\Event;

use CftfBundle\Entity\AbstractLsBase;
use CftfBundle\Entity\LsDoc;
use Symfony\Component\EventDispatcher\Event;

class NotificationEvent extends Event
{
    /**
     * @var string
     */
    protected $message;

    /**
     * @var LsDoc
     */
    protected $doc;

    /**
     * @var array
     *
     * Structure is:
     * [
     *   '{doc,item,assoc}-{a,u,d}' => [
     *     <id> => <identifier>,
     *     <object> (which is resolved to <id> => <identifier>)
     *   ]
     * ]
     */
    protected $changed;

    /**
     * @var string
     */
    protected $username;

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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function resolveChanged(): void
    {
        $orig = $this->changed;
        $new = [];

        foreach ($orig as $type => $set) {
            /**
             * @var string|int $key
             * @var string|AbstractLsBase $value
             */
            foreach ($set as $key => $value) {
                if (null === $value) {
                    continue;
                }
                if (is_object($value)) {
                    $new[$type][$value->getId()] = $value->getIdentifier();
                } else {
                    $new[$type][$key] = $value;
                }
            }
        }

        $this->changed = $new;
    }
}
