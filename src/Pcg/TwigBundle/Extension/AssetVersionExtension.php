<?php

namespace Pcg\TwigBundle\Extension;

use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class AssetVersionExtension.
 *
 * @DI\Service()
 * @DI\Tag("twig.extension")
 */
class AssetVersionExtension extends \Twig_Extension
{
    private $appDir;

    /**
     * @param string $appDir
     *
     * @DI\InjectParams({
     *     "appDir" = @DI\Inject("%kernel.root_dir%")
     * })
     */
    public function __construct($appDir)
    {
        $this->appDir = $appDir;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('asset_version', array($this, 'getAssetVersion')),
        );
    }

    public function getName()
    {
        return 'asset_version';
    }

    public function getAssetVersion($filename)
    {
        $manifestPath = $this->appDir.'/Resources/assets/rev-manifest.json';
        if (!file_exists($manifestPath)) {
            return $filename;
            //throw new \Exception(sprintf('Cannot find manifest file: "%s"', $manifestPath));
        }

        $paths = json_decode(file_get_contents($manifestPath), true);
        if (!isset($paths[$filename])) {
            return $filename;
            //throw new \Exception(sprintf('There is no file "%s" in the version manifest!', $filename));
        }

        return $paths[$filename];
    }
}
