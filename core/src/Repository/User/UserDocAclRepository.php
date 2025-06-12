<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\Framework\LsDoc;
use App\Entity\User\User;
use App\Entity\User\UserDocAcl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * UserDocAclRepository.
 *
 * @extends ServiceEntityRepository<UserDocAcl>
 */
class UserDocAclRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDocAcl::class);
    }

    /**
     * Find an ACL by the document and user.
     */
    public function findByDocUser(LsDoc $lsDoc, User $user): ?object
    {
        return $this->findOneBy(['lsDoc' => $lsDoc->getId(), 'user' => $user->getId()]);
    }
}
