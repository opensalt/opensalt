<?php

namespace App\Command\Email;

class SendAdminNotificationEmailCommand extends AbstractSendEmailCommand
{
    /**
     * @var string
     */
    protected $userName;

    public function __construct(string $recipient, string $userName)
    {
        parent::__construct($recipient);
        $this->userName = $userName;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->userName;
    }
}
