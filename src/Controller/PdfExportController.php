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
use Symfony\Component\Stopwatch\Stopwatch;
use Psr\Log\LoggerInterface;

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
    public function exportPdfAction(int $id,LoggerInterface $logger): StreamedResponse
    {
        $phpWordObject = new PhpWord();
        // Create a new Page
        $section = $phpWordObject->addSection();
        $stopwatch = new Stopwatch();
        $stopwatch->start('Fetch Data From DB');
        $response = $this->forward('App\Controller\Framework\CfPackageController:exportAction', ['id' => $id, '_format' => 'json']);
        $event = $stopwatch->stop('Fetch Data From DB');
        $timeTaken = $event->getDuration();
        $logger->info('Fetch Data From DB', array(
                       'Time Taken' => $timeTaken
                    ));
        $stopwatch->start('Html Render');
        $html = $this->renderView(
            'framework/doc_tree/export_pdf.html.twig',
            ['pdfData' => json_decode($response->getContent(), true)]
        );
        Html::addHtml($section, htmlentities($html));
        $event = $stopwatch->stop('Html Render');
        $timeTaken = $event->getDuration(); 
        $logger->info('Html Render==>', array(
                       'Time Taken' => $timeTaken
                    ));
        Settings::setPdfRendererName(Settings::PDF_RENDERER_TCPDF);
        Settings::setPdfRendererPath('../vendor/tecnickcom/tcpdf');
        $file = 'Framework.pdf';
        $stopwatch->start('Pdf Export');
        $response= new StreamedResponse(
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
        $event = $stopwatch->stop('Pdf Export');
        $timeTaken = $event->getDuration(); 
        $logger->info('Pdf Export==>', array(
                        // include extra "context" info in your logs
                       'Time Taken' => $timeTaken,
                    ));   
        return $response;
    }
}
