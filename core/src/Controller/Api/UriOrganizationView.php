<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Framework\LsItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UriOrganizationView extends AbstractController
{
    public function renderOrganizationView(LsItem $obj, Request $request, Response $response): Response
    {
        return $this->render('uri/organization_view.html.twig', [
            'obj' => $obj,
        ], $response);
    }
}
