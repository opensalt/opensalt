<?php

namespace Helper;

use Codeception\Module\Symfony;
use Novaway\Bundle\FeatureFlagBundle\Manager\FeatureManager;
use PHPUnit\Framework\SkippedWithMessageException;

class Toggles extends \Codeception\Module
{
    public function isFeatureEnabled(string $feature): bool
    {
        $config = $this->_getConfig('features');

        if (array_key_exists($feature, $config ?? [])) {
            return in_array($config[$feature], [true, 'true', 1, '1'], true);
        }

        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony2Module');

        $features = $symfony->grabService(FeatureManager::class);

        if ($features->isEnabled($feature)) {
            return true;
        }

        return false;
    }

    public function assertFeatureEnabled(string $feature): void
    {
        static $enabled = [];

        if (!isset($enabled[$feature])) {
            $enabled[$feature] = $this->isFeatureEnabled($feature);
        }

        if (!$enabled[$feature]) {
            throw new SkippedWithMessageException(sprintf('"%s" feature is disabled', $feature));
        }
    }
}
