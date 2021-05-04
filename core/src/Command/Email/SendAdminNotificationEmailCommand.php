<?php

namespace App\Command\Email;

class SendAdminNotificationEmailCommand extends AbstractSendEmailCommand
{
    protected string $userName;
    protected string $userOrganization;

    public function __construct(?string $recipient, string $userName, string $userOrganization)
    {
        parent::__construct($recipient);
        $this->userName = $userName;
        $this->userOrganization = $userOrganization;
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
