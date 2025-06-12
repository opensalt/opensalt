<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefGrade;

class UpdateGradeCommand extends BaseCommand
{
    public function __construct(private readonly LsDefGrade $grade)
    {
    }

    public function getGrade(): LsDefGrade
    {
        return $this->grade;
    }
}
