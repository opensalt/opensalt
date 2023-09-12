<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use App\Entity\User\User;
use Symfony\Component\Validator\Constraints as Assert;

class LockDocumentCommand extends BaseCommand
{
    /**
     * @var LsDoc
     */
    #[Assert\Type(LsDoc::class)]
    #[Assert\NotNull]
    private $doc;

    /**
     * @var User
     */
    private $user;

    public function __construct(LsDoc $doc, User $user)
    {
        $this->doc = $doc;
        $this->user = $user;
    }

    public function getDoc(): LsDoc
    {
        return $this->doc;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
