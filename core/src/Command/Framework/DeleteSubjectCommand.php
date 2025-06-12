<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefSubject;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteSubjectCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsDefSubject::class)]
        #[Assert\NotNull]
        private readonly LsDefSubject $subject,
    ) {
    }

    public function getSubject(): LsDefSubject
    {
        return $this->subject;
    }
}
