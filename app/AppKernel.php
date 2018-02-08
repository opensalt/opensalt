<?php

umask(0); // TODO: This should probably be in the constructor

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),

            new JMS\DiExtraBundle\JMSDiExtraBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),

            new Tetranz\Select2EntityBundle\TetranzSelect2EntityBundle(),

            new Csa\Bundle\GuzzleBundle\CsaGuzzleBundle(),

            new Nelmio\SecurityBundle\NelmioSecurityBundle(),
            new ActiveLAMP\Bundle\SwaggerUIBundle\ALSwaggerUIBundle(),
            new Nelmio\CorsBundle\NelmioCorsBundle(),

            new CftfBundle\CftfBundle(),
            new Salt\UserBundle\SaltUserBundle(),
            new GithubFilesBundle\GithubFilesBundle(),
            new Salt\SiteBundle\SaltSiteBundle(),
            new Liuggio\ExcelBundle\LiuggioExcelBundle(),
            new Qandidate\Bundle\ToggleBundle\QandidateToggleBundle(),
            new SimpleThings\EntityAudit\SimpleThingsEntityAuditBundle(),
        ];

        if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Matthimatiker\OpcacheBundle\MatthimatikerOpcacheBundle();
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
