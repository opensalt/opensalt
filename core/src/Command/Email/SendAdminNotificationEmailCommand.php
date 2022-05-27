<?php

namespace App\Command\Email;

class SendAdminNotificationEmailCommand extends AbstractSendEmailCommand
{
    public function __construct(?string $recipient, protected string $userName, protected string $userOrganization)
    {
        parent::__construct($recipient);
    }

    public function getUsername(): string
    {
        return $this->userName;
    }

    public function getOrganization(): string
    {
        return $this->userOrganization;
    }
}
