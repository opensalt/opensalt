<?php

namespace CftfBundle\Api\v1p1\Service;

use CftfBundle\Entity\LsDoc;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * Class ApiService
 *
 * @DI\Service("salt.api.v1p1.utils")
 */
class ApiUtilService
{
    public static $classMap = [
        LsDoc::class => 'api_v1p1_cfdocument',
    ];

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
    public function __construct($router)
    {
        $this->router = $router;
    }

    public function getApiUrl($obj)
    {
        $uri = $obj->getUri();

        if (empty($uri)) {
            return null;
        }

        if (!preg_match('/^local:/', $uri)) {
            return $uri;
        }

        $id = $obj->getIdentifier();

        $class = get_class($obj);
        if (!in_array($class, static::$classMap)) {
            return $this->router->generate(static::$classMap[$class], ['id' => $id], Router::ABSOLUTE_URL);
        }
    }
}
