<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Framework\IdentifiableInterface;
use App\Entity\Framework\LsDoc;
use Symfony\Contracts\EventDispatcher\Event;

class NotificationEvent extends Event
{
    /**
     * @var ?string The username of the user that made the change
     */
    protected ?string $username = null;

    public function __construct(
        protected string $msgId,
        /**
         * @var string Message to display/store about the change
         */
        protected string $message,
        protected ?LsDoc $doc,
        /**
         * @var array<string, array<mixed>> What changed
         *
         * Structure is:
         * [
         *   '{doc,item,assoc}-{a,u,d,l,ul}' => [
         *     <id> => <identifier>,
         *     <object> (which is resolved to <id> => <identifier>)
         *   ]
         * ]
         */
        protected array $changed = [],
        /**
         * @var bool Should the notification be displayed to the end user
         */
        protected bool $display = true,
    ) {
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

    /**
     * @return array<string, array<mixed>>
     *
     * Structure is:
     * [
     *   '{doc,item,assoc}-{a,u,d,l,ul}' => [
     *     <id> => <identifier>,
     *     <object> (which is resolved to <id> => <identifier>)
     *   ]
     * ]
     */
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
             * @var string|int                 $key
             * @var string|IdentifiableInterface|null $value
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
