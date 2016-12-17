<?php

namespace Cftf\AsnBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        $response = new JsonResponse();

        $fileUrl = $request->request->get('fileUrl');
        $asnDoc = $this->getAsnFile($fileUrl);

        $asnImport = $this->get('cftf_import.asn');
        $asnImport->parseAsnDocument($asnDoc);

        return $response->setData(array(
            'message' => 'Framework imported successfully!'
        ));
    }

    protected function getAsnFile($fileUrl){
        $client = new Client();

        $result = $client->request('GET', $fileUrl.'_full.json',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        return (string) $result->getBody();
    }
}
