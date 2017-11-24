<?php

namespace App\Controller;

use App\Service\AsnImport;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class AsnImportController
 *
 * @Security("is_granted('create', 'lsdoc')")
 */
class AsnImportController extends AbstractController
{
    /**
     * @Route("/cf/asn/import", name="import_from_asn")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function importAsnAction(Request $request, UserInterface $user, AsnImport $asnImport, ObjectManager $om)
    {
        $response = new JsonResponse();

        $fileUrl = $request->request->get('fileUrl');

        $lsDoc = $asnImport->generateFrameworkFromAsn($fileUrl);

        $lsDoc->setOrg($user->getOrg());
        $om->flush();

        return $response->setData([
            'message' => 'Framework imported successfully!',
        ]);
    }
}
