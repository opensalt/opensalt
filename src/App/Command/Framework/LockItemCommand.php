<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsItem;
use Salt\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class LockItemCommand extends BaseCommand
{
    /**
     * @var LsItem
     *
     * @Assert\Type(LsItem::class)
     * @Assert\NotNull()
     */
    private $item;

    /**
     * @var User
     */
    private $user;

    public function __construct(LsItem $item, User $user)
    {
        $this->item = $item;
        $this->user = $user;
    }

    public function getItem(): LsItem
    {
        return $this->item;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
