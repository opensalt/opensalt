<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User\User;
use App\Security\Permission;
use Milo\Github\Api;
use Milo\Github\OAuth\Token;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

        if (!in_array($currentUser->getGithubToken(), [null, ''], true)) {
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

        $url = empty($sha) ? '/repos/:owner/:repo/contents/:path' : '/repos/:owner/:repo/git/blobs/:sha';

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
        if (in_array(preg_match('/<([^>]+)>;\s*rel="'.preg_quote($rel, '/').'"/', $link, $match), [0, false], true)) {
            return null;
        }
        if (in_array(preg_match('/[^\d]*(\d+)/', $match[1], $totalPages), [0, false], true)) {
            return null;
        }

        return $totalPages[1];
    }
}
