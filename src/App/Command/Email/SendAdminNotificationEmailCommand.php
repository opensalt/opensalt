<?php

namespace App\Command\Email;

class SendAdminNotificationEmailCommand extends AbstractSendEmailCommand
{
  /**
   * @var string
   * @Assert\Email()
   */
  private $recipient;

  /**
   * @var string
   */
  protected $userName;

  public function __construct(string $recipient, string $userName)
  {
    parent::__construct($recipient);
    $this->recipient = $recipient;
    $this->userName = $userName;
  }

  /**
   * @return string
   */
  public function getRecipient(): string
  {
      return $this->recipient;
  }

  /**
   * @return string
   */
  public function getUsername(): string
  {
    return $this->$userName;
  }
}
