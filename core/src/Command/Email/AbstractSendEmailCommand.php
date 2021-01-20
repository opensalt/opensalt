<?php

namespace App\Command\Email;

use App\Command\BaseCommand;
use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractSendEmailCommand extends BaseCommand
{
    /**
     * @var string|null
     * @Assert\Email()
     */
    private $recipient;

    public function __construct(?string $recipient)
    {
        $this->recipient = $recipient;
    }


    public function getRecipient(): ?string
    {
        return $this->recipient;
    }
}
