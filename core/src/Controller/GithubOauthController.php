<?php

namespace App\Controller;

use App\Entity\User\User;
use App\Security\Permission;
use Milo\Github\Api;
use Milo\Github\OAuth\Token;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted(Permission::FRAMEWORK_CREATE)]
class GithubOauthController extends AbstractController
{
    #[Route(path: '/user/github/repos')]
    public function getRepos(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $response = new JsonResponse();

        if (!$currentUser instanceof User) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);

            return $response->setData([
                'message' => 'Please log in.',
            ]);
        }

        if (!empty($currentUser->getGithubToken())) {
            $page = $request->query->get('page');
            $perPage = $request->query->get('perPage');

            $token = new Token($currentUser->getGithubToken());
            $api = new Api();
            $api->setToken($token);

            $repos = $api->get('/user/repos?page='.$page.'&per_page='.$perPage);

            return $response->setData([
                'totalPages' => $this->parseLink($repos->getHeader('link'), 'last'),
                'data' => $api->decode($repos),
            ]);
        }

        $response->setStatusCode(Response::HTTP_UNAUTHORIZED);

        return $response->setData([
            'message' => 'Please log in with your GitHub account',
        ]);
    }

    #[Route(path: '/user/github/files')]
    public function getFiles(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $response = new JsonResponse();
        if (!$currentUser instanceof User) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);

            return $response->setData([
                'message' => 'Please log in.',
            ]);
        }

        $token = new Token($currentUser->getGithubToken());
        $api = new Api();
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

    private function parseLink(string $link, string $rel): ?string
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
