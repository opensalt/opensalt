<?php

namespace App\Service;

use App\Entity\Framework\IdentifiableInterface;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\Package;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class UriGenerator
{
    public const PACKAGE_PREFIX = 'p';

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getUri(?IdentifiableInterface $obj, ?string $route = null): ?string
    {
        // If no object then don't return a route
        if (null === $obj) {
            return null;
        }

        $id = $obj->getIdentifier();
        if ($obj instanceof LsDoc && Api1RouteMap::getForClass(Package::class) === $route) {
            $id = self::PACKAGE_PREFIX.$id;
        }

        return $this->getPublicUriForIdentifier($id);
    }

    public function getPublicUriForIdentifier(string $id): string
    {
        return $this->router->generate('uri_lookup', ['uri' => $id], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
