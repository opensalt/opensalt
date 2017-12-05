<?php

namespace App\Service;

use CftfBundle\Entity\LsDoc;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Salt\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FrameworkService
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry
     * @param TokenStorageInterface $tokenStorage
     * @param ValidatorInterface $validator
     */
    public function __construct(ManagerRegistry $registry, TokenStorageInterface $tokenStorage, ValidatorInterface $validator)
    {
        $this->tokenStorage = $tokenStorage;
        $this->em = $registry->getManager();
        $this->validator = $validator;
    }

    /**
     * @param LsDoc $doc
     * @return LsDoc
     * @throws \InvalidArgumentException
     */
    public function addDocument(LsDoc $doc): LsDoc
    {
        $this->em->persist($doc);

        // Determine the owner
        $user = $doc->getUser() ?? $this->getCurrentUser();
        if (null === $user) {
            $doc->setOwnedBy(null);

            return $doc;
        }

        // If the owner has already been set then return (such as for console commands)
        if (null !== $doc->getOrg()) {
            $doc->setUser(null);
            $doc->setOwnedBy('organization');

            return $doc;
        }

        // Set the user or org (default) based on what was stipulated
        if ('user' === $doc->getOwnedBy()) {
            $doc->setUser($user);
            $doc->setOrg(null);
        } else {
            $doc->setUser(null);
            $doc->setOrg($user->getOrg());
            $doc->setOwnedBy('organization');
        }

        return $doc;
    }

    /**
     * @param LsDoc $doc
     * @param \Closure|null $callback
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteFramework(LsDoc $doc, ?\Closure $callback = null): void
    {
        $this->em
            ->getRepository(LsDoc::class)
            ->deleteDocument($doc, $callback);
    }

    /**
     * @param LsDoc $doc
     */
    public function updateDocument(LsDoc $doc): void
    {
        $this->em->persist($doc);
    }

    /**
     * Get a user from the Security Token Storage.
     *
     * @return mixed
     *
     * @see TokenInterface::getUser()
     */
    protected function getCurrentUser(): ?User
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }
}
