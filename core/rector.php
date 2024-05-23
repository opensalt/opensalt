<?php

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Class_\StringableForToStringRector;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/templates',
        __DIR__ . '/config',
    ])
    ->withSkip([
        __DIR__ . '/config/bundles.php',
        RemoveUnusedVariableInCatchRector::class,
        StringableForToStringRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        NullToStrictStringFuncCallArgRector::class,
        ReadOnlyPropertyRector::class,
    ])
    ->withPhpSets(php81: true)
    //->withPreparedSets(typeDeclaration: true)
    //->withTypeCoverageLevel(1)
    ->withDeadCodeLevel(1)
    ->withAttributesSets(symfony: true, doctrine: true)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withSets([
        SymfonySetList::SYMFONY_64,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        //SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ])
;
