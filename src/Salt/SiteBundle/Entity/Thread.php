<?php

// src/MyProject/MyBundle/Entity/Thread.php

namespace Salt\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\CommentBundle\Entity\Thread as BaseThread;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="threads")
 * @ORM\Entity
 * @UniqueEntity("id")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Thread extends BaseThread
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="string", length=300, nullable=false)
     */
    protected $id;
}
