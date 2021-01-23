<?php

namespace App\Controller\Framework;

use App\Entity\ChangeEntry;
use App\Entity\Framework\LsDoc;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CfPackageController.
 *
 * @Route("/cfpackage")
 */
class CfPackageController extends AbstractController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Export a CFPackage.
     *
     * @Route("/doc/{id}.{_format}", requirements={"_format"="(json|html|pdf|csv)"}, methods={"GET"}, defaults={"_format"="json"}, name="cfpackage_export")
     * @Route("/doc/{id}/export.{_format}", requirements={"_format"="(json|html|pdf|csv)"}, methods={"GET"}, defaults={"_format"="json"}, name="cfpackage_export2")
     * @Template()
     */
    public function exportAction(Request $request, LsDoc $lsDoc, $_format = 'json')
    {
        $repo = $this->getDoctrine()->getRepository(LsDoc::class);

        if ('json' === $_format) {
            $response = $this->generateBaseResponse($lsDoc);

            if ($response->isNotModified($request)) {
                return $response;
            }

            $pkg = $repo->getPackageArray($lsDoc);

            $response->setContent($this->serializer->serialize(
                $pkg,
                $request->getRequestFormat('json'),
                SerializationContext::create()->setGroups(['Default', 'CfPackage'])
            ));

            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Content-Disposition', 'attachment; filename="opensalt-framework-'.$lsDoc->getIdentifier().'.json"');

            return $response;
        }

        return $this->generateSimplePackageArray($lsDoc);
    }

    /**
     * Generate a base response.
     */
    protected function generateBaseResponse(LsDoc $lsDoc): Response
    {
        $response = new Response();

        $changeRepo = $this->getDoctrine()->getRepository(ChangeEntry::class);
        $lastChange = $changeRepo->getLastChangeTimeForDoc($lsDoc);

        $lastModified = $lsDoc->getUpdatedAt();
        if (false !== $lastChange && null !== $lastChange['changed_at']) {
            $lastModified = new \DateTime($lastChange['changed_at'], new \DateTimeZone('UTC'));
        }
        $response->setEtag(md5($lastModified->format('U.u')), true);
        $response->setLastModified($lastModified);
        $response->setMaxAge(0);
        $response->setSharedMaxAge(0);
        $response->setExpires(\DateTime::createFromFormat('U', $lastModified->format('U'))->sub(new \DateInterval('PT1S')));
        $response->setPublic();
        $response->headers->addCacheControlDirective('must-revalidate');

        return $response;
    }

    /**
     * Generate an array representing the package.
     *
     * Note that this array does not match the API output
     */
    protected function generateSimplePackageArray(LsDoc $doc): array
    {
        $repo = $this->getDoctrine()->getRepository(LsDoc::class);

        $items = $repo->findAllItems($doc);
        $associations = $repo->findAllAssociations($doc);
        // PW: this used to use findAllAssociationsForCapturedNodes, but that wouldn't export crosswalk associations

        $itemTypes = [];
        foreach ($items as $item) {
            if (!empty($item['itemType'])) {
                $itemTypes[$item['itemType']['code']] = $item['itemType'];
            }
        }

        $arr = [
            'lsDoc' => $doc,
            'items' => $items,
            'associations' => $associations,
            'itemTypes' => $itemTypes,
            'subjects' => $doc->getSubjects(),
            'concepts' => [],
            'licences' => [],
            'associationGroupings' => [],
        ];

        return $arr;
    }
}
