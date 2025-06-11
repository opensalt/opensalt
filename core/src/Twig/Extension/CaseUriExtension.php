<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Entity\Framework\IdentifiableInterface;
use App\Service\IdentifiableObjectHelper;
use App\Service\UriGenerator;
use Symfony\Component\Routing\RouterInterface;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;
use Twig\Attribute\AsTwigTest;

readonly class CaseUriExtension
{
    public function __construct(
        private UriGenerator $uriGenerator,
        private RouterInterface $router,
        private IdentifiableObjectHelper $uriHelper,
    ) {
    }

    #[AsTwigFilter('object_uri')]
    #[AsTwigFunction('object_uri')]
    public function getObjectUri(?IdentifiableInterface $obj, ?string $route = null): ?string
    {
        if (null === $obj) {
            return null;
        }

        return $this->uriGenerator->getUri($obj, $route);
    }

    #[AsTwigFilter('uri_for_identifier')]
    #[AsTwigFunction('uri_for_identifier')]
    public function getUriForIdentifier(?string $identifier): ?string
    {
        if (null === $identifier || '' === $identifier) {
            return null;
        }

        if (str_starts_with($identifier, 'data:text')) {
            return $identifier;
        }

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

        if (null === $obj) {
            return null;
        }

        return $this->router->generate('uri_lookup', ['uri' => $obj->getIdentifier()], RouterInterface::ABSOLUTE_URL);
    }

    #[AsTwigFilter('local_or_remote_uri')]
    public function getLocalOrRemoteUri(?string $uri): ?string
    {
        if (null === $uri) {
            return null;
        }

        if (str_starts_with($uri, 'local:')) {
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
