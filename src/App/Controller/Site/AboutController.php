<?php

namespace App\Controller\Site;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AboutController extends AbstractController
{
    /**
     * @var string Value of kernel.root_dir
     */
    protected $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * @Route("/about", name="site_about")
     * @Template()
     */
    public function aboutAction()
    {
        $rootDir = $this->rootDir;
        $webDir = dirname($rootDir).'/web';

        if (file_exists($webDir.'/version.txt')) {
            $fullVersion = trim(file_get_contents($webDir.'/version.txt'));
        } elseif (file_exists($rootDir.'/../VERSION')) {
            $fullVersion = trim(file_get_contents($rootDir.'/../VERSION'));
        } else {
            $fullVersion = 'UNKNOWN';
        }

        return [
            'salt_version' => $fullVersion,
        ];
    }
}
