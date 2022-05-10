<?php

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Nelmio\SecurityBundle\NelmioSecurityBundle;
use Qandidate\Bundle\ToggleBundle\QandidateToggleBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Tetranz\Select2EntityBundle\TetranzSelect2EntityBundle;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

return [
    MonologBundle::class => ['all' => true],
    SensioFrameworkExtraBundle::class => ['all' => true],
    DoctrineBundle::class => ['all' => true],
    DoctrineMigrationsBundle::class => ['all' => true],
    DoctrineFixturesBundle::class => ['dev' => true, 'test' => true],
    NelmioSecurityBundle::class => ['all' => true],
    NelmioCorsBundle::class => ['all' => true],
    TetranzSelect2EntityBundle::class => ['all' => true],
    QandidateToggleBundle::class => ['all' => true],
    MakerBundle::class => ['dev' => true],
    FrameworkBundle::class => ['all' => true],
    WebProfilerBundle::class => ['dev' => true, 'test' => true],
    SecurityBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    DebugBundle::class => ['dev' => true, 'test' => true],
    TwigExtraBundle::class => ['all' => true],
    MercureBundle::class => ['all' => true],
    StofDoctrineExtensionsBundle::class => ['all' => true],
];
