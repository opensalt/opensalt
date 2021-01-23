<?php

namespace App\Service;

use App\Entity\Framework\IdentifiableInterface;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\Package;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Api1Uris
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var UriGenerator
     */
    private $uriGenerator;

    public function __construct(RouterInterface $router, UriGenerator $uriGenerator)
    {
        $this->router = $router;
        $this->uriGenerator = $uriGenerator;
    }

    public function getUri(?IdentifiableInterface $obj, ?string $route = null): ?string
    {
        return $this->generateUri($obj, $route, false);
    }

    public function getApiUrl(?IdentifiableInterface $obj, ?string $route = null): ?string
    {
        return $this->generateUri($obj, $route, true);
    }

    public function getApiUriForIdentifier(string $id, string $route): string
    {
        if (null === $route) {
            return null;
        }

        return $this->router->generate($route, ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    protected function generateUri(?IdentifiableInterface $obj, ?string $route = null, bool $isApiLink = false): ?string
    {
        if (null === $obj) {
            return null;
        }

        $uri = $obj->getUri();

        if (!preg_match('/^local:/', $uri)) {
            return $this->generateRemoteUri($uri, $route);
        }

        if (!$isApiLink) {
            return $this->uriGenerator->getUri($obj, $route);
        }

        if (null === $route) {
            $route = Api1RouteMap::getForObject($obj);
        }

        return $this->getApiUriForIdentifier($obj->getIdentifier(), $route);
    }

    protected function generateRemoteUri(string $uri, ?string $route = null): string
    {
        if (Api1RouteMap::getForClass(Package::class) === $route) {
            // Since we don't store the CF Package URI patch it
            $uri = str_replace('CFDocuments', 'CFPackages', $uri);
        }

        return $uri;
    }

    public function splitByComma(?string $csv): ?array
    {
        if (null === $csv) {
            return null;
        }

        $values = preg_split('/ *, */', $csv, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($values)) {
            return null;
        }

        return $values;
    }

    public function getLinkUriList(iterable $objs): ?array
    {
        $list = [];
        foreach ($objs as $obj) {
            $list[] = $this->getLinkUri($obj);
        }

        return $list;
    }

    public function getLinkUri(?IdentifiableInterface $obj, ?string $route = null): ?array
    {
        if (null === $obj) {
            return null;
        }

        $descriptors = [
            'getHumanCodingScheme',
            'getShortStatement',
            'getTitle',
            'getName',
        ];

        foreach ($descriptors as $descriptor) {
            if (method_exists($obj, $descriptor) && !empty($title = $obj->{$descriptor}())) {
                return [
                    'title' => $title,
                    'identifier' => $obj->getIdentifier(),
                    'uri' => $this->getUri($obj, $route),
                ];
            }
        }

        return [
            'title' => 'Linked Reference',
            'identifier' => $obj->getIdentifier(),
            'uri' => $this->getUri($obj, $route),
        ];
    }

    public function getNodeLinkUri($selector, LsAssociation $obj): ?array
    {
        $selector = ucfirst($selector);

        if (null === $obj) {
            return null;
        }

        if (!in_array($selector, ['Origin', 'Destination'])) {
            throw new \InvalidArgumentException('Selector may only be "Origin" or "Destination"');
        }

        $uri = $obj->{'get'.$selector}();
        if (is_object($uri)) {
            return $this->getLinkUri($uri);
        }

        $identifier = $obj->{'get'.$selector.'NodeIdentifier'}();

        if (preg_match('/^local:/', $uri)) {
            $uri = $this->uriGenerator->getPublicUriForIdentifier($identifier);
        }

        return [
            'title' => $selector.' node',
            'identifier' => $identifier,
            'uri' => $uri,
        ];
    }

    public function formatAssociationType(string $type): string
    {
        return lcfirst(str_replace(' ', '', $type));
    }
}
