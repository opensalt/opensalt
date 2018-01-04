<?php

namespace App\Controller;

use App\Command\CommandDispatcher;
use App\Command\Import\ImportAsnFromUrlCommand;
use Salt\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class AsnImportController
 *
 * @Security("is_granted('create', 'lsdoc')")
 */
class AsnImportController extends Controller
{
    use CommandDispatcher;

    public function __construct(ContainerInterface $container = null)
    {
        // event_dispatcher
        $this->setContainer($container);
    }

    /**
     * @Route("/cf/asn/import", name="import_from_asn")
     *
     * @param Request $request
     * @param UserInterface $user
     *
     * @return JsonResponse
     */
    public function importAsnAction(Request $request, UserInterface $user): Response
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ['message' => 'Invalid user']], Response::HTTP_UNAUTHORIZED);
        }

        $fileUrl = $request->request->get('fileUrl');
        $command = new ImportAsnFromUrlCommand($fileUrl, null, $user->getOrg());
        $this->sendCommand($command);

        return new JsonResponse([
            'message' => 'Framework imported successfully!',
        ]);
    }
}
