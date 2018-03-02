<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="cftf_index")
     */
    public function indexAction()
    {
        return $this->redirectToRoute('lsdoc_index');
    }
}
