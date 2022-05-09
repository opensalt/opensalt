<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Entity\Framework\LsDoc;
use App\Service\ExcelExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class ExcelExportController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(private ExcelExport $excelExport)
    {
    }

    #[Route(path: '/cfdoc/{id}/excel', name: 'export_excel_file', methods: ['GET'])]
    public function exportExcelAction(LsDoc $lsDoc): StreamedResponse
    {
        $title = preg_replace('/[^A-Za-z0-9]/', '_', $lsDoc->getTitle());

        $phpExcelObject = $this->excelExport->exportExcelFile($lsDoc);

        return new StreamedResponse(
            function () use ($phpExcelObject) {
                IOFactory::createWriter($phpExcelObject, 'Xlsx')
                    ->save('php://output');
            },
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$title.'.xlsx"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }
}
