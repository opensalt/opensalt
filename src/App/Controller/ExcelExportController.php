<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Service\ExcelExport;
use CftfBundle\Entity\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class ExcelExportController extends Controller
{
    use CommandDispatcherTrait;

    protected $excelExport;

    public function __construct(ContainerInterface $container = null, ExcelExport $excelExport)
    {
        // event_dispatcher
        $this->setContainer($container);
        $this->excelExport = $excelExport;
    }

    /**
     * @Route("/salt/case/export/{id}", name="export_case_file")
     *
     * @param LsDoc $lsDoc
     *
     * @return Response
     */
    public function exportExcelAction(LsDoc $lsDoc): Response
    {
        $title = preg_replace('/[^A-Za-z0-9]/', '_', $lsDoc->getTitle());

        $phpExcelObject = $this->excelExport->exportExcelFile($lsDoc);

        $response = $this->get('phpexcel')->createStreamedResponse(
            $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007'),
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment;filename='{$title}.xlsx'",
                'Cache-Control' => 'max-age=0',
            ]
        );

        return $response;
    }
}
