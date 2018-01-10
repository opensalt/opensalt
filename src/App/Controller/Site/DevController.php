<?php

namespace App\Controller\Site;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DevController extends AbstractController
{
    /**
     * @Route("/dev/cookie", name="dev_cookie")
     * @Security("has_role('ROLE_SUPER_USER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function devCookieAction(Request $request): Response
    {
        if (empty($cookie = $request->server->get('DEV_COOKIE'))) {
            $this->addFlash('error', 'Could not add development cookie as no DEV_COOKIE set.');

            return $this->redirectToRoute('cftf_index');
        }

        $response = $this->redirectToRoute('cftf_index');
        $response->headers->setCookie(new Cookie('dev', $cookie, 'now + 1 year'));
        $this->addFlash('success', 'Development cookie set.');

        return $response;
    }
}
