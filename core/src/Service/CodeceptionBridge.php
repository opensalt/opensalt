<?php

namespace App\Service;

use App\Service\User\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Novaway\Bundle\FeatureFlagBundle\Manager\FeatureManager;
use Symfony\Contracts\Service\Attribute\Required;

class CodeceptionBridge
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var FeatureManager
     */
    private $featureManager;

    /**
     * @var UserManager
     */
    private $userManager;

    #[Required]
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Required]
    public function setToggles(FeatureManager $featureManager)
    {
        $this->featureManager = $featureManager;
    }

    #[Required]
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
        return match ($service) {
            FeatureManager::class => $this->featureManager,
            UserManager::class => $this->userManager,
            default => null,
        };
    }
}
