<?php

namespace CftfBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="cftf_index")
     * @Template()
     */
    public function indexAction()
    {
        return $this->redirectToRoute('lsdoc_index');
    }
}
