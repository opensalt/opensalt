<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDefGrade;

class UpdateGradeCommand extends BaseCommand
{
    /**
     * @var LsDefGrade
     */
    private $grade;

    public function __construct(LsDefGrade $grade)
    {
        $this->grade = $grade;
    }

    public function getGrade(): LsDefGrade
    {
        return $this->grade;
    }
}
