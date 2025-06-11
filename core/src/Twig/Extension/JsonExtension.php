<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

class JsonExtension
{
    #[AsTwigFilter('json_decode')]
    #[AsTwigFunction('json_decode')]
    public function jsonDecode(string $string): string|array|null
    {
        return json_decode($string, true);
    }
}
