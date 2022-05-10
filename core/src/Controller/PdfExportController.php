<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Controller\Framework\CfPackageController;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class PdfExportController extends AbstractController
{
    use CommandDispatcherTrait;

    #[Route(path: '/cfdoc/{id}/pdf', name: 'export_pdf_file', methods: ['GET'])]
    public function exportPdf(int $id): StreamedResponse
    {
        $phpWordObject = new PhpWord();
        $section = $phpWordObject->addSection();

        $response = $this->forward(CfPackageController::class.'::export', ['id' => $id, '_format' => 'json']);
        $data_array = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        for ($i = 0, $iMax = is_countable($data_array['CFItems']) ? count($data_array['CFItems']) : 0; $i < $iMax; ++$i) {
            $data_array['CFItems'][$i]['fullStatement'] = $this->renderImages($data_array['CFItems'][$i]['fullStatement']);
            if (isset($data_array['CFItems'][$i]['notes'])) {
                $data_array['CFItems'][$i]['notes'] = $this->renderImages($data_array['CFItems'][$i]['notes']);
            }
        }

        $html = $this->renderView(
            'framework/doc_tree/export_pdf.html.twig',
            ['pdfData' => $data_array]
        );
        Html::addHtml($section, htmlentities($html));
        Settings::setPdfRendererName(Settings::PDF_RENDERER_TCPDF);
        Settings::setPdfRendererPath('../vendor/tecnickcom/tcpdf');
        $file = 'Framework.pdf';

        return new StreamedResponse(
            function () use ($phpWordObject) {
                IOFactory::createWriter($phpWordObject, 'PDF')
                    ->save('php://output');
            },
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/PDF',
                'Content-Disposition' => 'attachment; filename="'.$file.'"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    public function renderImages(string $string): string
    {
        $pattern = '/\!\[([^\]]*)\]\(((?:https?:\/\/|\/)[^\)]+)\)/';

        return preg_replace($pattern, '<img src = "$2">', $string);
    }
}
