<?php

namespace Cftf\AsnBundle\Controller;

use CftfBundle\Entity\LsDocRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Cftf\AsnBundle\Service\AsnImport;
use GuzzleHttp\Client;

/**
 * Class DefaultController
 *
 * @Security("is_granted('create', 'lsdoc')")
 */
class DefaultController extends Controller
{
    /**
     * @Route("/cf/asn/import", name="import_from_asn")
     */
    public function importAsnAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $response = new JsonResponse();

        $fileUrl = $request->request->get('fileUrl');
        $asnDoc = $this->getAsnFile($fileUrl);

        $asnImport = new AsnImport($em);
        $res = $asnImport->parseAsnDocument($asnDoc);

        return $response->setData(array(
            'message' => 'Framework imported successfully!'
        ));
    }

    protected function getAsnFile($fileUrl){
        $client = new Client();

        $result = $client->request('GET', $fileUrl . '_full.json',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        return (string) $result->getBody();
    }
}
