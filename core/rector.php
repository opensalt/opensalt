<?php

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Class_\StringableForToStringRector;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;

return RectorConfig::configure()
    ->withParallel()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/templates',
        __DIR__ . '/config',
    ])
    ->withPhpVersion(\Rector\ValueObject\PhpVersion::PHP_84)
    ->withPhpSets(php84: true)
    ->withComposerBased(
        symfony: true,
        twig: true,
        doctrine: true,
    )
    ->withSkip([
        __DIR__ . '/config/bundles.php',
        RemoveUnusedVariableInCatchRector::class,
        StringableForToStringRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        NullToStrictStringFuncCallArgRector::class,
        ReadOnlyPropertyRector::class,
        Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector::class,
        Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector::class,

        \Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector::class,
        Rector\Symfony\CodeQuality\Rector\Class_\InlineClassRoutePrefixRector::class,
        \Rector\DeadCode\Rector\ClassConst\RemoveUnusedPrivateClassConstantRector::class,
        \Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector::class,
        \Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector::class,
        \Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector::class,
        \Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector::class,
        Rector\Symfony\CodeQuality\Rector\Class_\ControllerMethodInjectionToConstructorRector::class,
        \Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector::class,
        \Rector\CodeQuality\Rector\Attribute\SortAttributeNamedArgsRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector::class,
        \Rector\CodeQuality\Rector\Class_\ConvertStaticToSelfRector::class,
        \Rector\CodeQuality\Rector\BooleanOr\RepeatedOrEqualToInArrayRector::class,
        \Rector\CodingStyle\Rector\FuncCall\StrictInArrayRector::class,
        AddArrowFunctionReturnTypeRector::class,
        DisallowedEmptyRuleFixerRector::class,
        \Rector\Symfony\CodeQuality\Rector\ClassMethod\RemoveUnusedRequestParamRector::class,
        Rector\TypeDeclaration\Rector\FuncCall\AddArrowFunctionParamArrayWhereDimFetchRector::class,
        \Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector::class,
        \Rector\Php70\Rector\Ternary\TernaryToNullCoalescingRector::class,
        \Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector::class,
        \Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector::class,
        \Rector\DeadCode\Rector\Cast\RecastingRemovalRector::class,
        \Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class,
        \Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector::class,
        \Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector::class,
        \Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector::class,
        \Rector\Php83\Rector\ClassConst\AddTypeToConstRector::class,
        \Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector::class,
        \Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector::class,
        \Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\NarrowObjectReturnTypeRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveParentDelegatingConstructorRector::class,
        \Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector::class,
        \Rector\Php80\Rector\Identical\StrStartsWithRector::class,
        \Rector\TypeDeclaration\Rector\Closure\ClosureReturnTypeRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRector::class,
        \Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector::class,
        \Rector\CodeQuality\Rector\If_\CombineIfRector::class,
        \Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector::class,
        \Rector\CodeQuality\Rector\Ternary\SimplifyTautologyTernaryRector::class,
        \Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector::class,
        \Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector::class,
        \Rector\CodingStyle\Rector\FuncCall\StrictArraySearchRector::class,
        \Rector\Php84\Rector\Foreach_\ForeachToArrayFindKeyRector::class,
        \Rector\TypeDeclaration\Rector\FuncCall\AddArrayFunctionClosureParamTypeRector::class,
        \Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector::class,
        \Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector::class,
        \Rector\TypeDeclaration\Rector\FuncCall\AddArrayFunctionClosureParamTypeRector::class,
        \Rector\TypeDeclaration\Rector\BooleanAnd\BinaryOpNullableToInstanceofRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector::class,
        \Rector\TypeDeclaration\Rector\FuncCall\AddArrayFunctionClosureParamTypeRector::class,
        \Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector::class,
        \Rector\CodeQuality\Rector\Class_\RemoveReadonlyPropertyVisibilityOnReadonlyClassRector::class,
        \Rector\TypeDeclaration\Rector\FuncCall\AddArrayFunctionClosureParamTypeRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector::class,
        \Rector\Php84\Rector\Foreach_\ForeachToArrayFindRector::class,
        \Rector\CodeQuality\Rector\If_\CompleteMissingIfElseBracketRector::class,
        \Rector\Php84\Rector\Foreach_\ForeachToArrayAnyRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        //naming: true,
        instanceOf: true,
        earlyReturn: true,
        strictBooleans: false,
        rectorPreset: true,
    )
    ->withAttributesSets(
        /*
        symfony: true,
        doctrine: true,
         */
    )
    ->withSets([
        \Rector\Set\ValueObject\LevelSetList::UP_TO_PHP_85,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_81,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_CODE_QUALITY,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ])
    ->withRules([
        // \Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector::class,
    ])
    //->withTypeCoverageLevel(1)
    //->withDeadCodeLevel(1)
    ->withImportNames(
        importShortClasses: false,
        removeUnusedImports: true,
    )
;
