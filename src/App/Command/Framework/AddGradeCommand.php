<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDefGrade;
use Symfony\Component\Validator\Constraints as Assert;

class AddGradeCommand extends BaseCommand
{
    /**
     * @var LsDefGrade
     *
     * @Assert\Type(LsDefGrade::class)
     * @Assert\NotNull()
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
