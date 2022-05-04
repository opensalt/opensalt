<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Basic\BracesFixer;
use PhpCsFixer\Fixer\Casing\ConstantCaseFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\ProtectedToPrivateFixer;
use PhpCsFixer\Fixer\ClassNotation\SelfAccessorFixer;
use PhpCsFixer\Fixer\ControlStructure\NoTrailingCommaInListCallFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\DeclareEqualNormalizeFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSummaryFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocVarWithoutNameFixer;
use PhpCsFixer\Fixer\ReturnNotation\ReturnAssignmentFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\CodingStandard\Fixer\Commenting\ParamReturnAndVarTagMalformsFixer;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $containerConfigurator->import(SetList::CLEAN_CODE);
    $containerConfigurator->import(SetList::PSR_12);
    $containerConfigurator->import(SetList::SYMFONY);

    $parameters->set('skip', [
        'PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer' => null, // Tries to add a space like &$param -> & $param in method var lists
        'SlevomatCodingStandard\Sniffs\Classes\UnusedPrivateElementsSniff.WriteOnlyProperty' => null,
        'SlevomatCodingStandard\Sniffs\PHP\UselessParenthesesSniff.UselessParentheses' => null,
        'SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff.MissingAnyTypeHint' => null,
        'SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff.MissingNativeTypeHint' => null,
        'SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff.MissingTraversableTypeHintSpecification' => null,
        'SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff.UselessAnnotation' => null,
        'SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff.MissingAnyTypeHint' => null,
        'SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff.MissingNativeTypeHint' => null,
        'SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff.MissingTraversableTypeHintSpecification' => null,
        'SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff.UselessAnnotation' => null,
        'SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff.MissingAnyTypeHint' => null,
        'SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff.MissingNativeTypeHint' => null,
        'SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff.MissingTraversableTypeHintSpecification' => null,
        'SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff.SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint' => null,
        'SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff.UselessAnnotation' => null,
        'SlevomatCodingStandard\Sniffs\Variables\UnusedVariableSniff.UnusedVariable' => null,
        'SlevomatCodingStandard\Sniffs\Variables\UselessVariableSniff.UselessVariable' => null,
        BinaryOperatorSpacesFixer::class => null,
        PhpdocAlignFixer::class => null,
        PhpdocSummaryFixer::class => null,
        PhpdocToCommentFixer::class => null,
        PhpdocVarWithoutNameFixer::class => null,
        ReturnAssignmentFixer::class => null,
        NoExtraBlankLinesFixer::class => null,
        ParamReturnAndVarTagMalformsFixer::class => null
    ]);
};
