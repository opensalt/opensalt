<?php

use Rector\Config\RectorConfig;
use Rector\Core\Configuration\Option;
use Rector\ValueObject\PhpVersion;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Symfony\Set\SymfonyLevelSetList;
use Rector\Symfony\Set\SymfonySetList;

return function (RectorConfig $rectorConfig): void {
    $rectorConfig->parallel();
    $rectorConfig->symfonyContainerXml(__DIR__.'/var/cache/dev/App_KernelDevDebugContainer.xml');
    //$parameters = $rectorConfig->parameters();

    $rectorConfig->paths([__DIR__.'/src', __DIR__.'/templates', __DIR__.'/config',/*__DIR__.'/local-src'*/]);
    $rectorConfig->phpVersion(PhpVersion::PHP_81);
    //$rectorConfig->phpstanConfig(__DIR__ . '/phpstan.neon.dist');
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::SYMFONY_64,
        //SymfonyLevelSetList::UP_TO_SYMFONY_64, // Deprecated
        SymfonySetList::SYMFONY_CODE_QUALITY,
        //DoctrineSetList::DOCTRINE_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        //SensiolabsSetList::FRAMEWORK_EXTRA_61,
    ]);

    /*
    $rectorConfig->skip([
\Rector\Class_\AnnotationToAttributeRector::class,
 \Rector\Removing\Rector\ClassMethod\ArgumentRemoverRector::class,
 * RenameClassRector
 * RenamePropertyRector
 * WrapReturnRector
 * AddParamTypeDeclarationRector
 * ChangeMethodVisibilityRector
    ]);
    */

    // From PHP 81 set
    $rectorConfig->rule(\Rector\Php52\Rector\Switch_\ContinueToBreakInSwitchRector::class);
    $rectorConfig->rule(\Rector\Php70\Rector\FuncCall\RandomFunctionRector::class);
    $rectorConfig->rule(\Rector\Php71\Rector\TryCatch\MultiExceptionCatchRector::class);
    $rectorConfig->rule(\Rector\Transform\Rector\ClassMethod\WrapReturnRector::class);
    //$rectorConfig->rule(\Rector\Php71\Rector\FuncCall\CountOnNullRector::class);
    $rectorConfig->rule(\Rector\Php73\Rector\FuncCall\ArrayKeyFirstLastRector::class);
    $rectorConfig->rule(\Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector::class);
    $rectorConfig->rule(\Rector\Php73\Rector\FuncCall\RegexDashEscapeRector::class);
    $rectorConfig->rule(\Rector\Php74\Rector\Assign\NullCoalescingOperatorRector::class);
    $rectorConfig->rule(\Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class);
    $rectorConfig->rule(\Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector::class);
    $rectorConfig->rule(\Rector\Php80\Rector\FuncCall\ClassOnObjectRector::class);
    //$rectorConfig->rule(\Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector::class);
    //$rectorConfig->rule(\Rector\Php80\Rector\FunctionLike\UnionTypesRector::class);
    $rectorConfig->rule(\Rector\Php81\Rector\ClassMethod\NewInInitializerRector::class);
    $rectorConfig->rule(\Rector\Removing\Rector\ClassMethod\ArgumentRemoverRector::class);
    $rectorConfig->rule(\Rector\Php81\Rector\ClassConst\FinalizePublicClassConstantRector::class);
    //$rectorConfig->rule(\Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector::class);
    $rectorConfig->rule(\Rector\Renaming\Rector\PropertyFetch\RenamePropertyRector::class);
    //NOTFOUND - $rectorConfig->rule(\Rector\Symfony\Rector\MethodCall\OptionNameRector::class);
    $rectorConfig->rule(\Rector\Transform\Rector\StaticCall\StaticCallToNewRector::class);
    $rectorConfig->rule(\Rector\Visibility\Rector\ClassMethod\ChangeMethodVisibilityRector::class);
    //$rectorConfig->rule(\Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class);
    $rectorConfig->rule(\Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector::class);

    $rectorConfig->rule(\Rector\Php80\Rector\Identical\StrStartsWithRector::class);
    $rectorConfig->rule(\Rector\Renaming\Rector\Name\RenameClassRector::class);
    // $rectorConfig->rule(\Rector\Php74\Rector\Property\TypedPropertyRector::class);  - not yet, but should go through them
    $rectorConfig->rule(\Rector\Renaming\Rector\PropertyFetch\RenamePropertyRector::class);
    $rectorConfig->rule(\Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector::class);
    //$rectorConfig->rule(\Rector\Php80\Rector\Class_\StringableForToStringRector::class); - not yet

    $rectorConfig->rule(\Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector::class);
    $rectorConfig->rule(\Rector\Php55\Rector\String_\StringClassNameToClassConstantRector::class);
    $rectorConfig->rule(\Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector::class);
    $rectorConfig->rule(\Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector::class);

    //NOTFOUND - $rectorConfig->rule(\Rector\Symfony\Rector\ClassMethod\ActionSuffixRemoverRector::class);
    //$rectorConfig->rule(\Rector\Symfony\Rector\ClassMethod\ResponseReturnTypeControllerActionRector::class);
    //NOTFOUND - $rectorConfig->rule(\Rector\Symfony\Rector\ClassMethod\TemplateAnnotationToThisRenderRector::class);

    /*
    */
    $rectorConfig->skip([
        //GONE - \Rector\Php71\Rector\FuncCall\CountOnNullRector::class, // some worked, some didn't
        \Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector::class, // will need to review
        //GONE - \Rector\Php80\Rector\FunctionLike\UnionTypesRector::class, // will need to review carefully
        \Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector::class,
        \Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class, // Probably a good idea
        //GONE - \Rector\Php74\Rector\Property\TypedPropertyRector::class, // Want to do later, but will take time to go through everything
        \Rector\Php80\Rector\Class_\StringableForToStringRector::class, // Probably should mark them at some point
    ]);

    //$rectorConfig->rule(\Rector\Symfony\Rector\Class_\MakeCommandLazyRector::class);
    //$rectorConfig->rule(\Rector\Symfony\Rector\ClassMethod\TemplateAnnotationToThisRenderRector::class);
    //GONE - $rectorConfig->rule(\Rector\Symfony\Rector\BinaryOp\ResponseStatusCodeRector::class);
    //Was looking for RectorInterface - $rectorConfig->rule(\Rector\PostRector\Rector\NameImportingPostRector::class);
    //$rectorConfig->rule(\Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector::class);

    // FQN classes are not imported by default. If you don't do it manually after every Rector run, enable it by:
    $rectorConfig->importNames();
    //GONE - $rectorConfig->disableImportShortClasses();
    // this will not import root namespace classes, like \DateTime or \Exception
    //$parameters->set(Option::IMPORT_SHORT_CLASSES, false);
};
