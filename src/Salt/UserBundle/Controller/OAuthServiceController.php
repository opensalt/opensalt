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
        $provider = new \League\OAuth2\Client\Provider\Github([
            'clientId'          => $this->getParameter('github_client_id'),
            'clientSecret'      => $this->getParameter('github_client_secret'),
            'redirectUri'       => $this->getParameter('github_redirect_uri'),
        ]);

        $code = $request->query->get('code');
        $state = $request->query->get('state');
        // User logged in
        $currentUser = $this->getUser();

        if (!isset($code)) {
            $options = [
                'scope' => ['user','user:email','repo']
            ];
            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl($options);
            $_SESSION['oauth2state'] = $provider->getState();
            return $this->redirect($authUrl);

        // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($state) || ($state !== $_SESSION['oauth2state'])) {

            unset($_SESSION['oauth2state']);
            throw new \Exception('Invalid state.');

        } else {
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('SaltUserBundle:User')->find($currentUser->getId());

            // Try to get an access token (using the authorization code grant)
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $code
            ]);

            // Set an access token per each user for fetch info.
            $user->setGithubToken($token->getToken());
            $em->flush();
            return $this->redirectToRoute('lsdoc_index');
        }
    }
}
