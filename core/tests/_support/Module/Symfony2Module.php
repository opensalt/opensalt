<?php

namespace Codeception\Module;

use App\Service\CodeceptionBridge;
use App\Service\User\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Qandidate\Toggle\ContextFactory;
use Qandidate\Toggle\ToggleManager;

class Symfony2Module extends Symfony
{
    public function _getEntityManager(): EntityManagerInterface
    {
        /** @var CodeceptionBridge $bridge */
        $bridge = $this->grabService(CodeceptionBridge::class);

        return $bridge->getEntityManager();
    }

    public function grabService(string $serviceId): object
    {
        switch ($serviceId) {
            case UserManager::class:
            case ToggleManager::class:
            case ContextFactory::class:
                /** @var CodeceptionBridge $bridge */
                $bridge = $this->grabService(CodeceptionBridge::class);

                return $bridge->grabService($serviceId);

            default:
        }

        return parent::grabService($serviceId);
    }
}
