<?php

namespace Salt\SiteBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * @Route("/about", name="site_about")
     * @Template()
     */
    public function aboutAction()
    {
        $rootDir = $this->getParameter('kernel.root_dir');
        $webDir = realpath($rootDir.'/../web');

        if (file_exists($webDir.'/version.txt')) {
            $fullVersion = trim(file_get_contents($webDir.'/version.txt'));
        } elseif (file_exists($rootDir.'/../VERSION')) {
            $pfxVersion = trim(file_get_contents($rootDir.'/../VERSION'));
            $fullVersion = $pfxVersion.'-dev';
        } else {
            $fullVersion = 'UNKNOWN';
        }

        return [
            'salt_version' => $fullVersion,
        ];
    }
}
