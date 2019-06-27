<?php

namespace App\Service;

use App\Entity\Framework\CfRubric;
use App\Entity\Framework\CfRubricCriterion;
use App\Entity\Framework\CfRubricCriterionLevel;
use App\Entity\Framework\IdentifiableInterface;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDefConcept;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\Package;

final class Api1RouteMap
{
    public static $routeMap = [
        Package::class => 'api_v1p0_cfpackage',
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

    public static function getForClass(string $class): ?string
    {
        return self::$routeMap[$class] ?? null;
    }

    public static function getForObject(IdentifiableInterface $obj): ?string
    {
        return self::findByClassName($obj) ?? self::findByInstance($obj);
    }

    protected static function findByClassName(IdentifiableInterface $obj): ?string
    {
        // Performance hack -- try to do a quick lookup of the class name
        $class = get_class($obj);
        $class = str_replace('Proxies\\__CG__\\', '', $class);

        return self::getForClass($class);
    }

    protected static function findByInstance(IdentifiableInterface $obj): ?string
    {
        foreach (static::$routeMap as $class => $route) {
            if ($obj instanceof $class) {
                return $route;
            }
        }

        return null;
    }
}
