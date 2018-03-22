<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use PhpOffice\PhpWord\IOFactory;
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
    public function exportPdfAction(int $id)
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
        Html::addHtml($section,htmlentities($html));
        Settings::setPdfRendererName(Settings::PDF_RENDERER_TCPDF);
        Settings::setPdfRendererPath('../vendor/tecnickcom/tcpdf');
        $file = 'Framework.pdf';
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        $writer = $this->get('phpword')->createWriter($phpWordObject, 'PDF');
        $writer->save("php://output");
    }
}
