<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfExportController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @Route("/cfdoc/{id}/pdf", methods={"GET"}, name="export_pdf_file")
     */
    public function exportPdfAction(int $id): StreamedResponse
    {
        $phpWordObject = new PhpWord();
        $section = $phpWordObject->addSection();

        $response = $this->forward('App\Controller\Framework\CfPackageController:exportAction', ['id' => $id, '_format' => 'json']);
        $data_array = json_decode($response->getContent(), true);
        for ($i = 0, $iMax = count($data_array['CFItems']); $i < $iMax; ++$i) {
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
            200,
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
