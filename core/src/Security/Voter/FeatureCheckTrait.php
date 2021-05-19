<?php

namespace App\Security\Voter;

use Qandidate\Toggle\ContextFactory;
use Qandidate\Toggle\ToggleManager;

trait FeatureCheckTrait
{
    private ToggleManager $toggleManager;
    private ContextFactory $toggleContextFactory;

    /**
     * @required
     */
    public function setToggleManager(ToggleManager $toggleManager, ContextFactory $contextFactory): void
    {
        $this->toggleManager = $toggleManager;
        $this->toggleContextFactory = $contextFactory;
    }

    public function hasActiveFeature(string $feature): bool
    {
        return $this->toggleManager->active($feature, $this->toggleContextFactory->createContext());
    }
}
