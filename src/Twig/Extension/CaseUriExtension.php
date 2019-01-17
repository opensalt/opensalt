<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class CaseUriExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('object_uri', [CaseUriRuntime::class, 'getObjectUri']),
            new TwigFilter('uri_for_identifier', [CaseUriRuntime::class, 'getUriForIdentifier']),
            new TwigFilter('local_uri', [CaseUriRuntime::class, 'getLocalUri']),
            new TwigFilter('local_remote_uri', [CaseUriRuntime::class, 'getLocalOrRemoteUri']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('object_uri', [CaseUriRuntime::class, 'getObjectUri']),
            new TwigFunction('uri_for_identifier', [CaseUriRuntime::class, 'getUriForIdentifier']),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('numeric', function ($value) {
                return is_numeric($value);
            }),
        ];
    }
}
