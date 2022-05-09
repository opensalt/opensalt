<?php

namespace App\Controller\Site;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AboutController extends AbstractController
{
    public function __construct(protected string $projectDir)
    {
    }

    #[Route(path: '/about', name: 'site_about')]
    public function about(): Response
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
