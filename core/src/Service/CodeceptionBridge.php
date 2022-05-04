<?php

namespace App\Service;

use App\Service\User\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Qandidate\Toggle\ContextFactory;
use Qandidate\Toggle\ToggleManager;

class CodeceptionBridge
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ToggleManager
     */
    private $toggleManager;

    /**
     * @var ContextFactory
     */
    private $contextFactory;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @required
     */
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @required
     */
    public function setToggles(ToggleManager $toggleManager, ContextFactory $contextFactory)
    {
        $this->toggleManager = $toggleManager;
        $this->contextFactory = $contextFactory;
    }

    /**
     * @required
     */
    public function setUserManager(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function grabService(string $service)
    {
        switch ($service) {
            case ToggleManager::class:
                return $this->toggleManager;
            case ContextFactory::class:
                return $this->contextFactory;
            case UserManager::class:
                return $this->userManager;
            default:
                return null;
        }
    }
}
