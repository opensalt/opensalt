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

    /**
     * @Route("/salt/importation_logs/mark_as_read", name="_importation_logs_mark_as_read")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function markAsReadAction(Request $request)
    {
        $response = new JsonResponse();
        $em = $this->getDoctrine()->getManager();

        $lsDocId = $request->request->get('lsDocId');
        $lsDoc = $em->getRepository('CftfBundle:LsDoc')->find(44);
        foreach ($lsDoc->getImportationLogs() as $log){
            $log->markAsRead();
            $em->persist($log);
            $em->flush();
        }

        return $response->setData([
            'message' => 'Logs marked as read successfully!'
        ]);
    }
}
