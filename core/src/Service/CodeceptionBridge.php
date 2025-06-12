<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\User\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Novaway\Bundle\FeatureFlagBundle\Manager\FeatureManager;
use Symfony\Contracts\Service\Attribute\Required;

class CodeceptionBridge
{
    private EntityManagerInterface $entityManager;

    private FeatureManager $featureManager;

    private UserManager $userManager;

    #[Required]
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    #[Required]
    public function setToggles(FeatureManager $featureManager): void
    {
        $this->featureManager = $featureManager;
    }

    #[Required]
    public function setUserManager(UserManager $userManager): void
    {
        $this->userManager = $userManager;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function grabService(string $service): FeatureManager|UserManager|null
    {
        return match ($service) {
            FeatureManager::class => $this->featureManager,
            UserManager::class => $this->userManager,
            default => null,
        };
    }
}
