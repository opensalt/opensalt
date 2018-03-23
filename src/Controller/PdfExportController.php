<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html;

class PdfExportController extends Controller
{
    use CommandDispatcherTrait;
    /**
     * @Route("/cfdoc/{id}/pdf", name="export_pdf_file")
     * @Method("GET")
     *
     * @param int $id
     */
    public function exportPdfAction(int $id, Request $request)
    {
        $phpWordObject = $this->get('phpword')->createPHPWordObject();
        // Create a new Page
        $section = $phpWordObject->addSection();

        $response = $this->forward('App\Controller\Framework\CfPackageController:exportAction', ['id' => $id, '_format' => 'json']);
        $html = $this->renderView(
            'framework/doc_tree/export_pdf.html.twig',
            array(
                    'pdfData' => json_decode($response->getContent(), true)
                 )
        );
        Html::addHtml($section, htmlentities($html));
        Settings::setPdfRendererName(Settings::PDF_RENDERER_TCPDF);
        Settings::setPdfRendererPath('../vendor/tecnickcom/tcpdf');
        $file = 'Framework.pdf';
        $writer = $this->get('phpword')->createWriter($phpWordObject, 'PDF');
        $response = new Response();

        // Set header
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file . '";');

        // Send headers before outputting anything
        $response->sendHeaders();

        $response->setContent(file_get_contents($writer->save('php://output')));

        return $response;
    }
}
