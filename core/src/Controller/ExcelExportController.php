<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Entity\Framework\LsDoc;
use App\Security\Permission;
use App\Service\ExcelExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ExcelExportController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly ExcelExport $excelExport,
        private readonly RateLimiterFactory $excelDownloadLimiter,
    ) {
    }

    #[Route(path: '/cfdoc/{id}/excel', name: 'export_excel_file', methods: ['GET'])]
    #[IsGranted(Permission::FRAMEWORK_DOWNLOAD_EXCEL, 'lsDoc')]
    public function exportExcel(Request $request, LsDoc $lsDoc): StreamedResponse
    {
        $limiter = $this->excelDownloadLimiter->create($request->getClientIp());
        if (false === $limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(600);
        }

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
