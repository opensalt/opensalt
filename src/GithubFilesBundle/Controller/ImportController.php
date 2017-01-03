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
class ImportController extends Controller{

    /**
     * @Route("/cf/github/import", name="import_from_github")
     */
    public function importAction(Request $request) {
        $lsdocKeys = $request->query->get('lsdocKeys');
        $fileContent = $request->request->get('fileContent');

        $githubImporter = $this->get('cftf_import.github');
        $githubImporter->parseGithubDocument($lsdocKeys, $fileContent);

        /* return $response->setData(array( */
        /*     'message' => 'Framework imported successfully!' */
        /* )); */
    }
}
