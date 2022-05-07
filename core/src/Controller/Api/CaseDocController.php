<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CaseDocController extends AbstractController
{
    #[Route(path: '/api/doc', methods: ['GET'], name: 'case_swagger_doc')]
    public function caseSwaggerDocAction(): Response
    {
        return $this->render('api/case_doc/case_swagger_doc.html.twig');
    }
}
