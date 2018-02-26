<?php

namespace App\Command\Email;

class SendAdminNotificationEmailCommand extends AbstractSendEmailCommand
{
    /**
     * @var string
     */
    protected $userName;

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
