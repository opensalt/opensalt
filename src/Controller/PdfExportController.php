<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfExportController extends Controller
{
    use CommandDispatcherTrait;
    /**
     * @Route("/cfdoc/{id}/pdf", name="export_pdf_file")
     * @Method("GET")
     *
     * @param int $id
     *
     * @return StreamedResponse
     */
    public function exportPdfAction(int $id): StreamedResponse
    {
        $phpWordObject = new PhpWord();
        // Create a new Page
        $section = $phpWordObject->addSection();

        $response = $this->forward('App\Controller\Framework\CfPackageController:exportAction', ['id' => $id, '_format' => 'json']);
        $html = $this->renderView(
            'framework/doc_tree/export_pdf.html.twig',
            ['pdfData' => json_decode($response->getContent(), true)]
        );
        Html::addHtml($section, htmlentities($html));
        Settings::setPdfRendererName(Settings::PDF_RENDERER_MPDF);
	Settings::setPdfRendererPath('../vendor/mpdf/mpdf');
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
}
