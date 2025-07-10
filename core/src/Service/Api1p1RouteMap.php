<?php

declare(strict_types=1);

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

final class Api1p1RouteMap
{
    /** @var array<class-string, string> */
    public static array $routeMap = [
        Package::class => 'api_v1p1_cfpackage',
        LsDoc::class => 'api_v1p1_cfdocument',
        LsItem::class => 'api_v1p1_cfitem',
        LsDefItemType::class => 'api_v1p1_cfitemtype',
        LsAssociation::class => 'api_v1p1_cfassociation',
        LsDefAssociationGrouping::class => 'api_v1p1_cfassociationgrouping',
        LsDefConcept::class => 'api_v1p1_cfconcept',
        LsDefLicence::class => 'api_v1p1_cflicense',
        LsDefSubject::class => 'api_v1p1_cfsubject',
        CfRubric::class => 'api_v1p1_cfrubric',
        CfRubricCriterion::class => 'api_v1p1_cfrubriccriterion',
        CfRubricCriterionLevel::class => 'api_v1p1_cfrubriccriterionlevel',
    ];

    public static function getForClass(string $class): ?string
    {
        return self::$routeMap[$class] ?? null;
    }

    public static function getForObject(IdentifiableInterface $obj): ?string
    {
        return self::findByClassName($obj) ?? self::findByInstance($obj);
    }

    private static function findByClassName(IdentifiableInterface $obj): ?string
    {
        // Performance hack -- try to do a quick lookup of the class name
        $class = $obj::class;
        $class = str_replace('Proxies\\__CG__\\', '', $class);

        return self::getForClass($class);
    }

    private static function findByInstance(IdentifiableInterface $obj): ?string
    {
        return array_find(self::$routeMap, fn ($class) => $obj instanceof $class);
    }
}
