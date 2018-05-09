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
        $data_array = json_decode($response->getContent(), true);
        for($i=0; $i < count($data_array['CFItems']); ++$i)
        {
            $data_array['CFItems'][$i]['fullStatement'] = $this->render_images($data_array['CFItems'][$i]['fullStatement']);
            if(isset($data_array['CFItems'][$i]['notes']))
            {
                $data_array['CFItems'][$i]['notes'] = $this->render_images($data_array['CFItems'][$i]['notes']);
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

    /**
     * @param string $string
     *
     * @return result
     */
    public function render_images($string)
    {
        $pattern = '/\!\[([^\]]*)\w*\]\(*\((\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|])\)/';
        $result = preg_replace($pattern, '$1 <img src = "$2">', $string);
        return $result;
    }
}
