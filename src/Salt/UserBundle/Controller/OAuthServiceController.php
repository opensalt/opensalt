<?php

namespace Salt\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * OAuth Service controller.
 *
 * @Route("login")
 */
class OAuthServiceController extends Controller
{
    /**
     * Save the Github Access Token.
     *
     * @Route("/check-github", name="github_login")
     * @Method("GET")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \UnexpectedValueException
     */
    public function githubAction(Request $request)
    {
        if ($this->container->hasParameter('github_redirect_uri')) {
            $redirectUri = $this->getParameter('github_redirect_uri');
        }
        if (empty($redirectUri)) {
            $redirectUri = $this->generateUrl(
                'github_login',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        $provider = new \League\OAuth2\Client\Provider\Github([
            'clientId'     => $this->getParameter('github_client_id'),
            'clientSecret' => $this->getParameter('github_client_secret'),
            'redirectUri'  => $redirectUri,
        ]);

        $code = $request->query->get('code');
        $state = $request->query->get('state');

        // User logged in
        $currentUser = $this->getUser();
        $session = $this->get('session');

        if (!isset($code)) {
            $options = [
                'scope' => ['user', 'user:email', 'repo'],
            ];
            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl($options);
            $session->set('oauth2state', $provider->getState());

            return $this->redirect($authUrl);
        // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($state) || ($state !== $session->get('oauth2state'))) {
            $session->remove('oauth2state');

            throw new \UnexpectedValueException('Invalid state.');
        } else {
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('SaltUserBundle:User')->find($currentUser->getId());

            // Try to get an access token (using the authorization code grant)
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);

            // Set an access token per each user for fetch info.
            $user->setGithubToken($token->getToken());
            $em->flush();

            return $this->redirectToRoute('lsdoc_index');
        }
    }
}
