<?php

namespace App\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

class CaseDocController
{
    /**
     * @Route("/api/doc", methods={"GET"}, name="case_swagger_doc")
     * @Template()
     */
    public function caseSwaggerDocAction()
    {
        return [];
    }
}
