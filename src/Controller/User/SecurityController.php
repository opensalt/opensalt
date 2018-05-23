<?php

namespace App\Controller\User;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @var AuthenticationUtils
     */
    private $authenticationUtils;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;

    public function __construct(AuthenticationUtils $authenticationUtils, AuthorizationCheckerInterface $authChecker)
    {
        $this->authenticationUtils = $authenticationUtils;
        $this->authChecker = $authChecker;
    }

    /**
     * @Route("/login", name="login")
     * @Template()
     */
    public function loginAction(Request $request)
    {
        $securityContext = $this->authChecker;
        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect('/');
        }

        // get the login error if there is one
        $error = $this->authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $this->authenticationUtils->getLastUsername();

        $redirect = $request->headers->get('referer');

        return [
            'last_username' => $lastUsername,
            'error'         => $error,
            'redirect'      => $redirect,
        ];
    }
}
