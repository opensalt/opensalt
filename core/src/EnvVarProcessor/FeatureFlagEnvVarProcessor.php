<?php

namespace App\EnvVarProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class FeatureFlagEnvVarProcessor implements EnvVarProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function getEnv(string $prefix, string $name, \Closure $getEnv): mixed
    {
        $env = $getEnv($name);

        if ('always-active' === $env) {
            return true;
        }

        return filter_var($env, \FILTER_VALIDATE_BOOL);
    }

    /**
     * {@inheritDoc}
     */
    public static function getProvidedTypes(): array
    {
        return [
            'feature_flag' => 'bool',
        ];
    }
}
