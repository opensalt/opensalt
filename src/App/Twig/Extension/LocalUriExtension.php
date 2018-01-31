<?php

namespace App\Twig\Extension;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * Class LocalUriExtension.
 *
 * @DI\Service()
 * @DI\Tag("twig.extension")
 */
class LocalUriExtension extends \Twig_Extension
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    private $router;

    /**
     * @param \Symfony\Bundle\FrameworkBundle\Routing\Router $router
     *
     * @DI\InjectParams({
     *     "router" = @DI\Inject("router")
     * })
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('local_uri', array($this, 'getLocalUri')),
            new \Twig_SimpleFilter('local_remote_uri', array($this, 'getLocalOrRemoteUri')),
        );
    }

    public function getName()
    {
        return 'local_uri';
    }

    public function getLocalUri($uri)
    {
        if (empty($uri)) {
            return null;
        }

        if (preg_match('/^local:/', $uri)) {
            $uri = preg_replace('/^local:/', '', $uri);
            $prefixed = $this->router->generate('editor_uri_lookup', ['uri'=>$uri], Router::ABSOLUTE_URL);

            return $prefixed;
        }

        return $this->router->generate('editor_uri_lookup', ['uri'=>$uri], Router::ABSOLUTE_URL);
    }

    public function getLocalOrRemoteUri($uri)
    {
        if (empty($uri)) {
            return null;
        }

        if (preg_match('/^local:/', $uri)) {
            return $this->getLocalUri($uri);
        }

        return $uri;
    }
}
