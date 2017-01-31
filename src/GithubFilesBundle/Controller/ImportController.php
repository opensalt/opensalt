<?php

namespace GithubFilesBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ImportController
 *
 * @Security("is_granted('create', 'lsdoc')")
 */
class ImportController extends Controller
{
    /**
     * @Route("/cf/github/import", name="import_from_github")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function importAction(Request $request)
    {
        $response = new JsonResponse();
        $lsDocKeys = $request->request->get('cfDocKeys');
        $lsItemKeys = $request->request->get('cfItemKeys');
        $fileContent = $request->request->get('content');

        $githubImporter = $this->get('cftf_import.github');
        $githubImporter->parseCSVGithubDocument($lsDocKeys, $lsItemKeys, base64_decode($fileContent));

        return $response->setData([
            'message' => 'Success',
        ]);
    }
}
