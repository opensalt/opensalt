<?php

namespace App\Security\Voter;

use Novaway\Bundle\FeatureFlagBundle\Manager\FeatureManager;
use Symfony\Contracts\Service\Attribute\Required;

trait FeatureCheckTrait
{
    private FeatureManager $featureManager;

    #[Required]
    public function setFeatureManager(FeatureManager $featureManager): void
    {
        $this->featureManager = $featureManager;
    }

    public function hasActiveFeature(string $feature): bool
    {
        return $this->featureManager->isEnabled($feature);
    }
}
