<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefSubject;

class UpdateSubjectCommand extends BaseCommand
{
    public function __construct(private readonly LsDefSubject $subject)
    {
    }

    public function getSubject(): LsDefSubject
    {
        return $this->subject;
    }
}
