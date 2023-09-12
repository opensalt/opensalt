<?php

namespace App\Command\Email;

use App\Command\BaseCommand;
use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractSendEmailCommand extends BaseCommand
{
    public function __construct(#[Assert\Email] private readonly ?string $recipient)
    {
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }
}
