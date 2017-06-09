<?php

namespace CftfBundle\Api\v1p0\Service;

use CftfBundle\Entity\CfRubric;
use CftfBundle\Entity\CfRubricCriterion;
use CftfBundle\Entity\CfRubricCriterionLevel;
use CftfBundle\Entity\IdentifiableInterface;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefAssociationGrouping;
use CftfBundle\Entity\LsDefConcept;
use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDefLicence;
use CftfBundle\Entity\LsDefSubject;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * Class ApiService
 *
 * @DI\Service("salt.api.v1p0.utils")
 */
class ApiUtilService
{
    public static $classMap = [
        LsDoc::class => 'api_v1p0_cfdocument',
        LsItem::class => 'api_v1p0_cfitem',
        LsDefItemType::class => 'api_v1p0_cfitemtype',
        LsAssociation::class => 'api_v1p0_cfassociation',
        LsDefAssociationGrouping::class => 'api_v1p0_cfassociationgrouping',
        LsDefConcept::class => 'api_v1p0_cfconcept',
        LsDefLicence::class => 'api_v1p0_cflicense',
        LsDefSubject::class => 'api_v1p0_cfsubject',
        CfRubric::class => 'api_v1p0_cfrubric',
        CfRubricCriterion::class => 'api_v1p0_cfrubriccriterion',
        CfRubricCriterionLevel::class => 'api_v1p0_cfrubriccriterionlevel',
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

    /**
     * @param mixed $obj
     * @param string|null $route
     *
     * @return null|string
     */
    public function getApiUrl(?IdentifiableInterface $obj, ?string $route = null): ?string
    {
        // Only get one URI
        if (is_array($obj)) {
            $obj = current($obj);
        }

        // If no object then don't return a route
        if (null === $obj) {
            return null;
        }

        $uri = $obj->getUri();

        if (empty($uri)) {
            return null;
        }

        if (!preg_match('/^local:/', $uri)) {
            return $uri;
        }

        $id = $obj->getIdentifier();

        if (null === $route) {
            $class = get_class($obj);
            $class = str_replace('Proxies\\__CG__\\', '', $class);
            if (!in_array($class, static::$classMap, true)) {
                $route = static::$classMap[$class];
            }
        }

        if (empty($route)) {
            return null;
        }

        return $this->getApiUriForIdentifier($id, $route);
    }

    public function getApiUriForIdentifier(string $id, string $route): string
    {
        return $this->router->generate($route, ['id' => $id], Router::ABSOLUTE_URL);
    }

    /**
     * @param string|null $csv
     *
     * @return array|null
     */
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

    /**
     * @param iterable|null $objs
     *
     * @return array
     */
    public function getLinkUriList(?iterable $objs)
    {
        if (null === $objs) {
            return null;
        }

        $list = [];

        foreach ($objs as $obj) {
            $list[] = $this->getLinkUri($obj);
        }

        return $list;
    }

    /**
     * @param mixed $obj
     * @param null|string $route
     *
     * @return array|null
     */
    public function getLinkUri(?IdentifiableInterface $obj, ?string $route = null): ?array
    {
        if (null === $obj) {
            return null;
        }

        if (method_exists($obj, 'getHumanCodingScheme')) {
            $title = $obj->getHumanCodingScheme();
        }
        if (empty($title) && method_exists($obj, 'getShortStatement')) {
            $title = $obj->getShortStatement();
        }
        if (empty($title) && method_exists($obj, 'getTitle')) {
            $title = $obj->getTitle();
        }
        if (empty($title) && method_exists($obj, 'getName')) {
            $title = $obj->getName();
        }

        if (empty($title)) {
            $title = 'Linked Reference';
        }

        return [
            'title' => $title,
            'identifier' => $obj->getIdentifier(),
            'uri' => $this->getApiUrl($obj, $route),
        ];
    }

    public function getNodeLinkUri($selector, LsAssociation $obj): ?array
    {
        if (null === $obj) {
            return null;
        }

        $title = $selector;

        if ('origin' === $selector) {
            $uri = $obj->getOrigin();
            if (is_object($uri)) {
                return $this->getLinkUri($uri);
            }
            $identifier = $obj->getOriginNodeIdentifier();
        } else {
            $uri = $obj->getDestination();
            if (is_object($uri)) {
                return $this->getLinkUri($uri);
            }
            $identifier = $obj->getDestinationNodeIdentifier();
        }


        return [
            'title' => ucfirst($title).' Node',
            'identifier' => $identifier,
            'uri' => $uri,
        ];
    }

    public function formatAssociationType(string $type): string
    {
        return lcfirst(str_replace(' ', '', $type));
    }
}
