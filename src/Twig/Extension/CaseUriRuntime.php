<?php

namespace App\Twig\Extension;

use App\Entity\Framework\IdentifiableInterface;
use App\Service\UriGenerator;
use App\Service\IdentifiableObjectHelper;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\RuntimeExtensionInterface;

class CaseUriRuntime implements RuntimeExtensionInterface
{
    /**
     * @var UriGenerator
     */
    private $uriGenerator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var IdentifiableObjectHelper
     */
    private $objectHelper;

    public function __construct(UriGenerator $uriGenerator, RouterInterface $router, IdentifiableObjectHelper $uriHelper)
    {
        $this->uriGenerator = $uriGenerator;
        $this->router = $router;
        $this->objectHelper = $uriHelper;
    }

    public function getObjectUri(?IdentifiableInterface $obj, ?string $route = null): ?string
    {
        if (null === $obj) {
            return null;
        }

        return $this->uriGenerator->getUri($obj, $route);
    }

    public function getUriForIdentifier(?string $identifier): ?string
    {
        if (null === $identifier) {
            return null;
        }

        return $this->uriGenerator->getPublicUriForIdentifier($identifier);
    }

    public function getLocalUri(?string $uri): ?string
    {
        if (null === $uri) {
            return null;
        }

        if (preg_match('/^local:/', $uri)) {
            $uri = preg_replace('/^local:/', '', $uri);

            return $this->getUriForIdentifier($uri);
        }

        $obj = $this->objectHelper->findObjectByUri($uri);

        if (null === $obj) {
            return null;
        }

        return $this->router->generate('uri_lookup', ['uri' => $obj->getIdentifier()], RouterInterface::ABSOLUTE_URL);
    }

    public function getLocalOrRemoteUri($uri): ?string
    {
        if (null === $uri) {
            return null;
        }

        if (preg_match('/^local:/', $uri)) {
            return $this->getLocalUri($uri);
        }

        return $uri;
    }
}
