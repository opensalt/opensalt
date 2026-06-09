<?php

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Mercure\FrankenPhpHub;

class MercureAutodetectPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        // 1. Check if the native FrankenPHP function exists
        if (!\function_exists('mercure_publish') || !\class_exists(FrankenPhpHub::class)) {
            return;
        }

        // 2. Locate the default Mercure hub service definitions
        $hubIds = ['mercure.hub.default', 'mercure.hub.default.publisher', 'mercure.hub.default.subscriber'];

        foreach ($hubIds as $id) {
            if ($container->hasDefinition($id)) {
                $definition = $container->getDefinition($id);

                // 3. Swap the target class to the native FrankenPHP Hub
                $definition->setClass(FrankenPhpHub::class);

                // 4. Clear any configured HTTP arguments (like the URL string/env var)
                // FrankenPhpHub doesn't require an external URL argument
                $definition->setArguments([]);
            }
        }
    }
}
