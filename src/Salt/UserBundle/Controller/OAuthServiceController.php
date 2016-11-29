<?php

namespace Salt\UserBundle\Controller;

use Salt\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * OAuth Service controller.
 *
 * @Route("login")
 *
 */
class OAuthServiceController extends Controller
{
    /**
     * Save the Github Access Token.
     *
     * @Route("/check-github", name="github_login")
     * @Method("GET")
     *
     */
    public function githubAction(Request $request)
    {
        $userSession = $this->getUser();
        $codeAccessTokenUser = $request->query->get('code');
        if( empty($userSession) ){
            return $this->redirectToRoute('login');
        }else{
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('SaltUserBundle:User')->find($userSession->getId());
            $user->setGithubToken($codeAccessTokenUser);
            $em->flush();
            return $this->redirectToRoute('lsdoc_index');
        }
    }
}
