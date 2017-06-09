<?php

namespace Cftf\SbacBundle\Controller;

use CftfBundle\Entity\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CresstController extends Controller
{
    const ELA_ROOT_UUID = '9100ef5e-d793-4184-bb5b-3686d0258549';
    const MATH_ROOT_UUID = 'c44427b4-8e4e-540f-a68b-dcdb1b7ff78d';
    const ELA_PUBLICATION = 'TA-ELA-v1';
    const MATH_PUBLICATION = 'TA-Math-v1';

    /**
     * @Route("/sbac/dl/export/{id}.{_format}", name="dl_export", defaults={"_format"="html"}, requirements={"id"="\d+", "_format"="(html|csv)"})
     * @Template()
     *
     * @param Request $request
     * @param LsDoc $lsDoc
     * @param string $_format
     *
     * @return Response|array
     */
    public function exportAction(Request $request, LsDoc $lsDoc, $_format = 'html')
    {
        $csvLines = $this->get('sbac.cresst.csv')->generateDlCsv($lsDoc);

        if ('csv' === $_format) {
            $response = new Response(
                $this->renderView(
                    'CftfSbacBundle:Cresst:export.csv.twig',
                    [
                        'csvLines' => $csvLines,
                    ]
                ),
                200
            );
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename=cresst-ela.csv');
            $response->headers->set('Pragma', 'no-cache');

            return $response;
        }

        return [
            'csvLines' => $csvLines,
        ];
    }
}
