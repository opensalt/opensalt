<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/core/src')
;

$config = new PhpCsFixer\Config();
return $config ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'phpdoc_align' => false,
        'phpdoc_to_comment' => false,
        'binary_operator_spaces' => false,
    ])
    ->setFinder($finder)
;
