<?php

namespace Salt\SiteBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/about", name="site_about")
     * @Template()
     */
    public function aboutAction()
    {
        $rootDir = $this->getParameter('kernel.root_dir');
        $webDir = realpath($rootDir.'/../web');

        if (file_exists($webDir.'/version.txt')) {
            $fullVersion = trim(file_get_contents($webDir.'/version.txt'));
        } elseif (file_exists($rootDir.'/../VERSION')) {
            $fullVersion = trim(file_get_contents($rootDir.'/../VERSION'));
        } else {
            $fullVersion = 'UNKNOWN';
        }

        return [
            'salt_version' => $fullVersion,
        ];
    }

    /**
     * @Route("/salt/case/import", name="import_case_file")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function importAction(Request $request)
    {
        $response = new JsonResponse();
        $content = base64_decode($request->request->get('fileContent'));
        $fileContent = json_decode($content);

        $caseImporter = $this->get('cftf_import.case');
        $caseImporter->importCaseFile($fileContent);

        return $response->setData([
            'message' => 'Success'
        ]);
    }
}
