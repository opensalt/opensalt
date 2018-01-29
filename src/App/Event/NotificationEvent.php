<?php

namespace App\Event;

use CftfBundle\Entity\AbstractLsBase;
use CftfBundle\Entity\LsDoc;
use Symfony\Component\EventDispatcher\Event;

class NotificationEvent extends Event
{
    /**
     * @var string Message to display/store about the change
     */
    protected $message;

    /**
     * @var LsDoc
     */
    protected $doc;

    /**
     * @var array What changed
     *
     * Structure is:
     * [
     *   '{doc,item,assoc}-{a,u,d,l,ul}' => [
     *     <id> => <identifier>,
     *     <object> (which is resolved to <id> => <identifier>)
     *   ]
     * ]
     */
    protected $changed;

    /**
     * @var string The username of the user that made the change
     */
    protected $username;

    /**
     * @var string
     */
    protected $msgId;

    /**
     * @var bool Should the notification be displayed to the end user
     */
    protected $display;

    public function __construct(string $messageId, string $message, ?LsDoc $doc, array $changed = [], $display = true)
    {
        $this->msgId = $messageId;
        $this->message = $message;
        $this->doc = $doc;
        $this->changed = $changed;
        $this->display = $display;
    }

    public function getMessageId(): string
    {
        return $this->msgId;
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
                if (\is_object($value)) {
                    $new[$type][$value->getId()] = $value->getIdentifier();
                } else {
                    $new[$type][$key] = $value;
                }
            }
        }

        $this->changed = $new;
    }
}
