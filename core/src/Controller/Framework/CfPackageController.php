<?php

namespace App\Controller\Framework;

use App\Entity\ChangeEntry;
use App\Entity\Framework\LsDoc;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class CfPackageController.
 */
#[Route(path: '/cfpackage')]
class CfPackageController extends AbstractController
{
    public function __construct(
        private SerializerInterface $symfonySerializer,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Export a CFPackage.
     */
    #[Route(path: '/doc/{id}.{_format}', requirements: ['_format' => '(json|html|pdf|csv)'], methods: ['GET'], defaults: ['_format' => 'json'], name: 'cfpackage_export')]
    #[Route(path: '/doc/{id}/export.{_format}', requirements: ['_format' => '(json|html|pdf|csv)'], methods: ['GET'], defaults: ['_format' => 'json'], name: 'cfpackage_export2')]
    public function exportAction(Request $request, LsDoc $lsDoc, string $_format = 'json'): Response
    {
        if ('json' === $_format) {
            $response = $this->generateBaseResponse($lsDoc);

            if ($response->isNotModified($request)) {
                return $response;
            }

            $response->setContent(
                $this->symfonySerializer->serialize($lsDoc, 'json', [
                    'groups' => ['default', 'CfPackage'],
                    'json_encode_options' => \JSON_UNESCAPED_SLASHES|\JSON_PRESERVE_ZERO_FRACTION,
                    'generate-package' => 'v1p0',
                ])
            );

            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Content-Disposition', 'attachment; filename="opensalt-framework-'.$lsDoc->getIdentifier().'.json"');

            return $response;
        }

        if (!in_array($_format, ['json', 'html', 'pdf', 'csv'])) {
            $_format = 'json';
        }

        return $this->render('framework/cf_package/export.'.$_format.'.twig', $this->generateSimplePackageArray($lsDoc));
    }

    /**
     * Generate a base response.
     */
    protected function generateBaseResponse(LsDoc $lsDoc): Response
    {
        $response = new Response();

        $changeRepo = $this->managerRegistry->getRepository(ChangeEntry::class);
        $lastChange = $changeRepo->getLastChangeTimeForDoc($lsDoc);

        $lastModified = $lsDoc->getUpdatedAt();
        if (null !== ($lastChange['changed_at'] ?? null)) {
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
        $repo = $this->managerRegistry->getRepository(LsDoc::class);

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
