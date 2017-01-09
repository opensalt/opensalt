<?php

namespace GithubFilesBundle\Controller;

use Milo\Github;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultController
 *
 * @Security("is_granted('create', 'lsdoc')")
 */
class DefaultController extends Controller
{
    /**
     * @Route("/user/github/repos")
     */
    public function reposAction()
    {
        $currentUser = $this->getUser();
        $response = new JsonResponse();
        $token = new \Milo\Github\OAuth\Token($currentUser->getGithubToken());
        $api = new \Milo\Github\Api();
        $api->setToken($token);

        $respos = $api->get('/user/repos');

        return $response->setData(array(
            'data' => $api->decode($respos)
        ));
    }

    /**
     * @Route("/user/github/files")
     */
    public function getFilesAction(Request $request){
        $currentUser = $this->getUser();
        $response = new JsonResponse();
        $token = new \Milo\Github\OAuth\Token($currentUser->getGithubToken());
        $api = new \Milo\Github\Api();
        $api->setToken($token);

        $owner = $request->query->get('owner');
        $repoName = $request->query->get('repo');
        $path = $request->query->get('path');

        $listFiles = $api->get('/repos/:owner/:repo/contents/:path', [
            'owner' => $owner,
            'repo' => $repoName,
            'path' => $path
        ]);

        return $response->setData(array(
            'data' => $api->decode($listFiles)
        ));
    }
}
