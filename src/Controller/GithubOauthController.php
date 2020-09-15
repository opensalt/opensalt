<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GithubOauthController
 *
 * @Security("is_granted('create', 'lsdoc')")
 */
class GithubOauthController extends AbstractController
{
    /**
     * @Route("/user/github/repos")
     *
     * @return JsonResponse
     */
    public function getReposAction(Request $request)
    {
        $currentUser = $this->getUser();
        $response = new JsonResponse();

        if (!empty($currentUser->getGithubToken())) {
            $page = $request->query->get('page');
            $perPage = $request->query->get('perPage');

            $token = new \Milo\Github\OAuth\Token($currentUser->getGithubToken());
            $api = new \Milo\Github\Api();
            $api->setToken($token);

            $repos = $api->get('/user/repos?page='.$page.'&per_page='.$perPage);

            return $response->setData([
                'totalPages' => $this->parseLink($repos->getHeader('link'), 'last'),
                'data' => $api->decode($repos),
            ]);
        }

        $response->setStatusCode(401);
        return $response->setData([
            'message' => 'Please log in with your GitHub account',
        ]);
    }

    /**
     * @Route("/user/github/files")
     *
     * @return JsonResponse
     */
    public function getFilesAction(Request $request)
    {
        $currentUser = $this->getUser();
        $response = new JsonResponse();
        $token = new \Milo\Github\OAuth\Token($currentUser->getGithubToken());
        $api = new \Milo\Github\Api();
        $api->setToken($token);

        $owner = $request->query->get('owner');
        $repoName = $request->query->get('repo');
        $sha = $request->query->get('sha');
        $path = $request->query->get('path');

        if (empty($sha)) {
            $url = '/repos/:owner/:repo/contents/:path';
        } else {
            $url = '/repos/:owner/:repo/git/blobs/:sha';
        }

        $blob = $api->get($url, [
            'owner' => $owner,
            'repo' => $repoName,
            'sha' => $sha,
            'path' => $path,
        ]);

        return $response->setData([
            'data' => $api->decode($blob),
        ]);
    }

    /**
     * @param string $link
     * @param string $rel
     *
     * @return int
     */
    private function parseLink($link, $rel)
    {
        if (!preg_match('/<([^>]+)>;\s*rel="'.preg_quote($rel, '/').'"/', $link, $match)) {
            return null;
        }
        if (!preg_match('/[^\d]*(\d+)/', $match[1], $totalPages)) {
            return null;
        }

        return $totalPages[1];
    }

}
