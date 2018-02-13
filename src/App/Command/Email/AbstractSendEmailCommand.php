<?php

namespace App\Command\Email;

use App\Command\BaseCommand;
use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractSendEmailCommand extends BaseCommand
{
    /**
     * @var string
     * @Assert\Email()
     */
    private $recipient;

    public function __construct(string $recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * @return string
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }
}
