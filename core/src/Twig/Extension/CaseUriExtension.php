<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Entity\Framework\IdentifiableInterface;
use App\Service\IdentifiableObjectHelper;
use App\Service\UriGenerator;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;
use Twig\Attribute\AsTwigTest;

readonly class CaseUriExtension
{
    public function __construct(
        private UriGenerator $uriGenerator,
        private IdentifiableObjectHelper $uriHelper,
    ) {
    }

    #[AsTwigFilter('object_uri')]
    #[AsTwigFunction('object_uri')]
    public function getObjectUri(?IdentifiableInterface $obj, ?string $route = null): ?string
    {
        return $this->uriGenerator->getUri($obj, $route);
    }

    #[AsTwigFilter('uri_for_identifier')]
    #[AsTwigFunction('uri_for_identifier')]
    public function getUriForIdentifier(?string $identifier): ?string
    {
        return $this->uriGenerator->getPublicUriForIdentifier($identifier);
    }

    #[AsTwigFilter('local_uri')]
    public function getLocalUri(?string $uri): ?string
    {
        if (null === $uri) {
            return null;
        }

        if (str_starts_with($uri, 'local:')) {
            $uri = preg_replace('/^local:/', '', $uri);

            return $this->getUriForIdentifier($uri);
        }

        $obj = $this->uriHelper->findObjectByUri($uri);

        return $this->uriGenerator->getUri($obj);
    }

    #[AsTwigFilter('local_remote_uri')]
    public function getLocalOrRemoteUri(?string $uri): ?string
    {
        if (str_starts_with($uri ?? '', 'local:')) {
            return $this->getLocalUri($uri);
        }

        return $uri;
    }

    #[AsTwigTest('numeric')]
    public function isNumeric(mixed $value): bool
    {
        return is_numeric($value);
    }
}
