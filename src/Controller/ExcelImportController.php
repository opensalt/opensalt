<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\ImportExcelFileCommand;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExcelImportController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @Route("/salt/excel/import", methods={"POST"}, name="import_excel_file")
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return Response
     */
    public function importExcelAction(Request $request): Response
    {
        $file = $request->files->get('file');

        $command = new ImportExcelFileCommand($file->getRealPath());
        $this->sendCommand($command);

        return new Response('OK', Response::HTTP_OK);
    }
}
