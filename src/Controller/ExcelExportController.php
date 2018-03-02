<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Service\ExcelExport;
use App\Entity\Framework\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @var ExcelExport
     */
    protected $excelExport;

    public function __construct(ExcelExport $excelExport)
    {
        $this->excelExport = $excelExport;
    }

    /**
     * @Route("/cfdoc/{id}/excel", name="export_excel_file")
     * @Method("GET")
     *
     * @param LsDoc $lsDoc
     *
     * @return StreamedResponse
     */
    public function exportExcelAction(LsDoc $lsDoc): StreamedResponse
    {
        $title = preg_replace('/[^A-Za-z0-9]/', '_', $lsDoc->getTitle());

        $phpExcelObject = $this->excelExport->exportExcelFile($lsDoc);

        return new StreamedResponse(
            function () use ($phpExcelObject) {
                \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($phpExcelObject, 'Xlsx')
                    ->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$title.'.xlsx"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }
}
