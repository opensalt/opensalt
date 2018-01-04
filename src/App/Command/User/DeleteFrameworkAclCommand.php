<?php

namespace App\Command\User;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDoc;
use Salt\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteFrameworkAclCommand extends BaseCommand
{
    /**
     * @var LsDoc
     *
     * @Assert\Type(LsDoc::class)
     * @Assert\NotNull()
     */
    private $doc;

    /**
     * @var User
     *
     * @Assert\Type(User::class)
     * @Assert\NotNull()
     */
    private $user;

    public function __construct(LsDoc $doc, User $user)
    {
        $this->doc = $doc;
        $this->user = $user;
    }

    /**
     * @return LsDoc
     */
    public function getDoc(): LsDoc
    {
        return $this->doc;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
