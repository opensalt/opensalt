<?php

namespace Salt\SiteBundle\Controller;

use CftfBundle\Entity\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    private $smartLevel = [];

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
     * @Route("/salt/case/export/{id}", name="export_case_file")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportExcelAction(LsDoc $lsDoc)
    {
        $repo = $this->getDoctrine()->getManager()->getRepository(LsDoc::class);
        $response = new Response();

        $items = $repo->findAllChildrenArray($lsDoc);
        $topChildren = $repo->findTopChildrenIds($lsDoc);
        $associations = $repo->findAllAssociations($lsDoc);

        $i = 1;
        foreach ($topChildren as $id) {
            $this->smartLevel[$id] = $i;
            $item = $items[$id];

            if (count($item['children']) > 0) {
                $this->getSmartLevelAction($item['children'], $id, $items);
            }
            ++$i;
        }

        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();

        $caseExporter = $this->get('cftf_export.case');
        $caseExporter->exportCaseFile($lsDoc, $items, $this->smartLevel, $associations, $phpExcelObject);

        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
        $response = $this->get('phpexcel')->createStreamedResponse($writer);

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="case.xlsx"');
        $response->headers->set('Cache-Control', ' max-age=0');

        return $response;
    }

    /**
     * @Route("/salt/import_logs/mark_as_read", name="mark_import_logs_as_read")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function markAsReadAction(Request $request)
    {
        $response = new JsonResponse();
        $em = $this->getDoctrine()->getManager();

        $lsDocId = $request->query->get('lsDocId');
        $lsDoc = $em->getRepository('CftfBundle:LsDoc')->find($lsDocId);

        foreach ($lsDoc->getImportLogs() as $log){
            $log->markAsRead();
        }
        $em->flush();

        return $response->setData([
            'message' => 'Logs marked as read successfully!'
        ]);
    }

    public function getSmartLevelAction($items, $parentId, $itemsArray) {
        $j = 1;

        foreach ($items as $item) {
            $item = $itemsArray[$item['id']];
            $this->smartLevel[$item['id']] = $this->smartLevel[$parentId].'.'.$j;

            if (count($item['children']) > 0) {
                $this->getSmartLevelAction($item['children'], $item['id'], $itemsArray);
            }

            ++$j;
        }
    }
}
