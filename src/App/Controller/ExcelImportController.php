<?php

namespace App\Controller;

use App\Command\CommandDispatcher;
use App\Command\Import\ImportExcelFileCommand;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExcelImportController extends AbstractController
{
    use CommandDispatcher;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @Route("/salt/excel/import", name="import_excel_file")
     * @Method("POST")
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
