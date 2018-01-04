<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDefSubject;
use Symfony\Component\Validator\Constraints as Assert;

class AddSubjectCommand extends BaseCommand
{
    /**
     * @var LsDefSubject
     *
     * @Assert\Type(LsDefSubject::class)
     * @Assert\NotNull()
     */
    private $subject;

    public function __construct(LsDefSubject $subject)
    {
        $this->subject = $subject;
    }

    public function getSubject(): LsDefSubject
    {
        return $this->subject;
    }
}
