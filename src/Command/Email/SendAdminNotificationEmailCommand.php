<?php

namespace App\Command\Email;

class SendAdminNotificationEmailCommand extends AbstractSendEmailCommand
{
    /**
     * @var string
     */
    protected $userName;

    /**
     * @var string
     */
    protected $userOrganization;

    public function __construct(string $recipient, string $userName, string $userOrganization)
    {
        parent::__construct($recipient);
        $this->userName = $userName;
        $this->userOrganization = $userOrganization;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->userName;
    }

    /**
     * @return string
     */
    public function getOrganization(): string
    {
        return $this->userOrganization;
    }
}
