<?php

namespace GithubFilesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Milo\Github;

class DefaultController extends Controller
{
    /**
     * @Route("/user/github/files")
     */
    public function indexAction()
    {
        $currentUser = $this->getUser();
        $response = new JsonResponse();
        $token = new \Milo\Github\OAuth\Token($currentUser->getGithubToken());
        $api = new \Milo\Github\Api;
        $api->setToken($token);

        $respos = $api->get('/user/repos');

        return $response->setData(array(
            'data' => $api->decode($respos)
        ));
    }
}
