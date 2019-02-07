<?php

namespace App\Controller\Site;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AboutController extends AbstractController
{
    /**
     * @var string Value of kernel.project_dir
     */
    protected $projectDir;

    public function __construct(string $projectDir)
    {
        $this->$projectDir = $projectDir;
    }

    /**
     * @Route("/about", name="site_about")
     * @Template()
     */
    public function aboutAction()
    {
        if (file_exists($this->projectDir.'/public/version.txt')) {
            $fullVersion = trim(file_get_contents($this->projectDir.'/public/version.txt'));
        } elseif (file_exists($this->projectDir.'/VERSION')) {
            $fullVersion = trim(file_get_contents($this->projectDir.'/VERSION'));
        } else {
            $fullVersion = 'UNKNOWN';
        }

        return [
            'salt_version' => $fullVersion,
        ];
    }
}
