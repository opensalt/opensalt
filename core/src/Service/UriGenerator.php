<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Framework\IdentifiableInterface;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\Package;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class UriGenerator
{
    final public const string PACKAGE_PREFIX = 'p';

    public function __construct(private readonly RouterInterface $router)
    {
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

        $frameworkId = null;
        if ($obj instanceof LsItem) {
            $frameworkId = $obj->getLsDocIdentifier();
        }

        return $this->getPublicUriForIdentifier($id, $frameworkId);
    }

    public function getPublicUriForIdentifier(?string $id, ?string $frameworkId = null): ?string
    {
        if (null === $id || '' === $id) {
            return null;
        }

        if (str_starts_with($id, 'data:text')) {
            return $id;
        }

        if (null === $frameworkId) {
            return $this->router->generate('uri_lookup', ['uri' => $id], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $this->router->generate('uri_lookup_framework', ['uri' => $id, 'framework' => $frameworkId], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
