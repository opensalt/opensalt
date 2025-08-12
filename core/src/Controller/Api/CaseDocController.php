<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CaseDocController extends AbstractController
{
    #[Route(path: '/api/doc', name: 'case_swagger_doc', methods: ['GET'])]
    public function caseSwaggerDoc(): Response
    {
        return $this->redirectToRoute('case10_swagger_doc');
    }

    #[Route(path: '/api/doc/case-1-0', name: 'case10_swagger_doc', methods: ['GET'])]
    public function case10SwaggerDoc(): Response
    {
        return $this->render('api/case_doc/case10_swagger_doc.html.twig');
    }

    #[Route(path: '/api/doc/case-1-1', name: 'case11_swagger_doc', methods: ['GET'])]
    public function case11SwaggerDoc(): Response
    {
        return $this->render('api/case_doc/case11_swagger_doc.html.twig');
    }
}
