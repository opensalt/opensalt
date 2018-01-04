<?php

namespace Helper;

use Codeception\Exception\Skip;
use Codeception\Module\Symfony;

class Toggles extends \Codeception\Module
{
    public function isFeatureEnabled(string $feature): bool
    {
        $config = $this->_getConfig('features');

        if (array_key_exists($feature, $config ?? [])) {
            return in_array($config[$feature], [true, 'true', 1, '1'], true);
        }

        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');

        $toggles = $symfony->grabService('qandidate.toggle.manager');
        $context = $symfony->grabService('qandidate.toggle.context_factory');

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
            throw new Skip(sprintf('"%s" feature is disabled', $feature));
        }
    }
}
