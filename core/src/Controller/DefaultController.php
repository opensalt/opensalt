<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route(path: '/', name: 'salt_index')]
    public function indexAction()
    {
        return $this->redirectToRoute('lsdoc_index');
    }
}
