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
    public function getReposAction(Request $request)
    {
        $page = $request->query->get('page');
        $perPage = $request->query->get('perPage');

        $currentUser = $this->getUser();
        $response = new JsonResponse();
        $token = new \Milo\Github\OAuth\Token($currentUser->getGithubToken());
        $api = new \Milo\Github\Api();
        $api->setToken($token);

        $repos = $api->get('/user/repos?page='.$page.'&per_page='.$perPage);

        return $response->setData(array(
            'totalPages' => static::parseLink($repos ->getHeader('link'), 'last'),
            'data' => $api->decode($repos)
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
        $sha = $request->query->get('sha');

        if( empty($sha)){
            $url = "/repos/:owner/:repo/contents/";
        }else{
            $url = "/repos/:owner/:repo/git/blobs/:sha";
        }

        $blob = $api->get($url, [
            'owner' => $owner,
            'repo' => $repoName,
            'sha' => $sha
        ]);

        return $response->setData(array(
            'data' => $api->decode($blob)
        ));
    }

    public static function parseLink($link, $rel){
        if (!preg_match('(<([^>]+)>;\s*rel="' . preg_quote($rel) . '")', $link, $match)) {
            return NULL;
        }
        if (!preg_match('([^\d]*(\d+))', $match[1], $total_pages)) {
            return NULL;
        }
        return $total_pages[1];
    }

}
