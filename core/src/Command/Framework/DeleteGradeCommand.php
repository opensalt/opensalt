<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefGrade;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteGradeCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsDefGrade::class)]
        #[Assert\NotNull]
        private readonly LsDefGrade $grade,
    ) {
    }

    public function getGrade(): LsDefGrade
    {
        return $this->grade;
    }
}
