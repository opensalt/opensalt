<?php

namespace App\Controller\Site;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AboutController extends AbstractController
{
    /**
     * @var string Value of kernel.project_dir
     */
    protected $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    #[Route(path: '/about', name: 'site_about')]
    public function aboutAction(): Response
    {
        if (file_exists($this->projectDir.'/public/version.txt')) {
            $fullVersion = trim(file_get_contents($this->projectDir.'/public/version.txt'));
        } elseif (file_exists($this->projectDir.'/VERSION')) {
            $fullVersion = trim(file_get_contents($this->projectDir.'/VERSION'));
        } else {
            $fullVersion = 'UNKNOWN';
        }

        return $this->render('site/about/about.html.twig', [
            'salt_version' => $fullVersion,
        ]);
    }
}
