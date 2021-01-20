<?php

namespace Helper;

use Codeception\Module\Symfony;
use PHPUnit\Framework\SkippedTestError;
use Qandidate\Toggle\ContextFactory;
use Qandidate\Toggle\ToggleManager;

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

        $toggles = $symfony->grabService(ToggleManager::class);
        $context = $symfony->grabService(ContextFactory::class);

        if ($toggles->active($feature, $context->createContext())) {
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
            throw new SkippedTestError(sprintf('"%s" feature is disabled', $feature));
        }
    }
}
