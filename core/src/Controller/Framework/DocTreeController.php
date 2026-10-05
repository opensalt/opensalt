<?php

declare(strict_types=1);

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddExternalDocCommand;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\ChangeEntryRepository;
use App\Repository\Framework\LsDefAssociationGroupingRepository;
use App\Repository\Framework\LsDocRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/cf-tree')]
class DocTreeController extends AbstractController
{
    use CommandDispatcherTrait;
    use RedirectsToFrameworkEditorTrait;

    private const string ETAG_SEED = '2';

    public function __construct(
        private readonly DoctrineDbalAdapter $externalDocCache,
        private readonly LsDocRepository $docRepository,
        private readonly LsDefAssociationGroupingRepository $associationGroupingRepository,
        private readonly ChangeEntryRepository $changeEntryRepository,
        private readonly ?string $caseNetworkClientId,
        private readonly ?string $caseNetworkClientSecret,
        private readonly ?string $caseNetworkScope,
        private readonly ?string $caseNetworkTokenEndpoint,
    ) {
    }

    #[Route(path: '/doc/{slug}', name: 'doc_tree_view', requirements: ['slug' => '[a-zA-Z0-9.-]+'], defaults: ['lsItemId' => null], methods: ['GET'])]
    #[Route(path: '/doc/{slug}/av', name: 'doc_tree_view_av', requirements: ['slug' => '[a-zA-Z0-9.-]+'], defaults: ['lsItemId' => null], methods: ['GET'])]
    #[Route(path: '/doc/{slug}/lv', name: 'doc_tree_view_log', requirements: ['slug' => '[a-zA-Z0-9.-]+'], defaults: ['lsItemId' => null], methods: ['GET'])]
    #[Route(path: '/doc/{slug}/{assocGroup}', name: 'doc_tree_view_ag', requirements: ['slug' => '[a-zA-Z0-9.-]+'], defaults: ['lsItemId' => null], methods: ['GET'])]
    public function view(
        Request $request,
        #[MapEntity(expr: 'repository.findOneBySlug(slug)')] LsDoc $lsDoc,
    ): Response {
        $route = $request->attributes->getString('_route');

        return match ($route) {
            'doc_tree_view_av' => $this->redirectToFrameworkEditor($lsDoc, 'association'),
            'doc_tree_view_log' => $this->redirectToFrameworkEditor($lsDoc, 'log'),
            default => $this->redirectToFrameworkEditor($lsDoc),
        };
    }

    /**
     * Export a CFPackage in a special json format designed for efficiently loading the package's data to the OpenSALT doctree client.
     */
    #[Route(path: '/docexport/{id}.json', name: 'doctree_cfpackage_export', methods: ['GET'])]
    public function export(Request $request, LsDoc $lsDoc): JsonResponse
    {
        $response = new JsonResponse();

        $lastChange = $this->changeEntryRepository->getLastChangeTimeForDoc($lsDoc);

        $lastModified = $lsDoc->getUpdatedAt();
        if (null !== ($lastChange['changed_at'] ?? null)) {
            $lastModified = new \DateTime($lastChange['changed_at'], new \DateTimeZone('UTC'));
        }
        $response->setEtag(md5($lastModified->format('U.u').self::ETAG_SEED), true);
        $response->setLastModified($lastModified);
        $response->setMaxAge(0);
        $response->setSharedMaxAge(0);
        $response->setExpires(\DateTime::createFromFormat('U', $lastModified->format('U'))->sub(new \DateInterval('PT1S')));
        $response->setPublic();
        $response->headers->addCacheControlDirective('must-revalidate');

        if ($response->isNotModified($request)) {
            return $response;
        }

        $items = $this->docRepository->findItemsForExportDoc($lsDoc);
        $associations = $this->docRepository->findAssociationsForExportDoc($lsDoc);
        $groupIds = [];
        foreach ($associations as $association) {
            if (($association['group']['identifier'] ?? null) !== null) {
                $groupIds[$association['group']['identifier']] = 1;
            }
        }
        $assocGroups = $this->associationGroupingRepository->findByIdentifiers(array_keys($groupIds));
        $associatedDocs = array_merge(
            $lsDoc->getExternalDocs(),
            $this->docRepository->findAssociatedDocs($lsDoc)
        );

        $docAttributes = [
            'baseDoc' => $lsDoc->getAttribute('baseDoc'),
            'associatedDocs' => $associatedDocs,
        ];

        $itemTypes = [];
        foreach ($items as $key => $item) {
            $items[$key]['objectType'] = LsItem::objectTypeForDiscriminator($item['discriminator']);
            if (!empty($item['itemType'])) {
                $itemTypes[$item['itemType']['code']] = $item['itemType'];
            }
        }

        $arr = [
            'lsDoc' => $lsDoc,
            'docAttributes' => $docAttributes,
            'items' => $items,
            'associations' => $associations,
            'itemTypes' => $itemTypes,
            'subjects' => $lsDoc->getSubjects(),
            'concepts' => [],
            'licences' => [$lsDoc->getLicence()],
            'assocGroups' => $assocGroups,
        ];

        $response->setContent($this->renderView('framework/doc_tree/export.json.twig', $arr));

        // This is called to retrieve a response by other methods, so cannot use a template
        return $response;
    }

    /**
     * Retrieve a CFPackage from the given document identifier, then use export() to export it.
     *
     * @throws \Exception
     */
    #[Route(path: '/retrievedocument/{id}', name: 'doctree_retrieve_document', methods: ['GET'])]
    #[Route(path: '/retrievedocument/url', name: 'doctree_retrieve_document_by_url', defaults: ['id' => null], methods: ['GET'])]
    public function retrieveDocument(Request $request, ?LsDoc $lsDoc = null): Response
    {
        ini_set('memory_limit', '1G');

        // $request could contain an id...
        if ($id = $request->query->get('id')) {
            // in this case it has to be a document on this OpenSALT instantiation
            return $this->respondWithDocumentById($request, (int) $id);
        }

        // or an identifier...
        if (null !== $lsDoc && $identifier = $request->query->get('identifier')) {
            // first see if it's referencing a document on this OpenSALT instantiation
            return $this->respondWithDocumentByIdentifier($request, $identifier, $lsDoc);
        }

        // or a url...
        if ($url = $request->query->get('url')) {
            // try to load the url, noting that we should save a record of it in externalDocs if found
            return $this->exportExternalDocument($url, $lsDoc);
        }

        return new Response('Document not found.', Response::HTTP_NOT_FOUND);
    }

    /**
     * @throws GuzzleException
     */
    protected function exportExternalDocument(string $url, ?LsDoc $lsDoc = null): Response
    {
        // Check the cache for the document
        $cache = $this->externalDocCache;
        $cacheDoc = $cache->getItem(rawurlencode($url));
        if ($cacheDoc->isHit()) {
            $document = $cacheDoc->get();
        } else {
            try {
                $document = $this->fetchExternalDocument($url);
            } catch (RequestException $e) {
                $error = $e->getResponse();

                throw new NotFoundHttpException($error->getReasonPhrase());
            } catch (\Exception $e) {
                $error = $e->getMessage();

                throw new NotFoundHttpException($error);
            }

            // Save document in cache for 30 minutes (arbitrary time period)
            $cacheDoc->set($document);
            $cacheDoc->expiresAfter(new \DateInterval('PT30M'));
            $cache->save($cacheDoc);
        }

        if (empty($document)) {
            throw new NotFoundHttpException('Document not found.');
        }

        // if $lsDoc is not empty, get the document'document identifier and title and save to the $lsDoc'document externalDocs
        if (null !== $lsDoc) {
            $this->addExternalDocumentToDoc($url, $lsDoc, $document);
        }

        // now return the file
        return new Response(
            $document,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/json',
                'Pragma' => 'no-cache',
            ]
        );
    }

    protected function isCaseNetworkUrl(string $url): bool
    {
        preg_match('|casenetwork\.imsglobal\.org|', $url, $matches);
        if ([] !== $matches) {
            return true;
        }

        return false;
    }

    protected function retrieveCaseNetworkBearerToken(): string
    {
        try {
            $jsonClient = new Client();
            $response = $jsonClient->request(
                'POST',
                $this->caseNetworkTokenEndpoint,
                [
                    'timeout' => 6000,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Accept' => 'application/json',
                        'User-Agent' => 'OpenSALT',
                    ],
                    'auth' => [$this->caseNetworkClientId, $this->caseNetworkClientSecret],
                    'http_errors' => true,
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'scope' => $this->caseNetworkScope,
                    ],
                ]
            );

            return json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR)->access_token;
        } catch (RequestException $e) {
            $message = $e->getHandlerContext();

            throw new NotFoundHttpException($message['error']);
        } catch (\Exception) {
            throw new NotFoundHttpException('Document not found.');
        }
    }

    #[Route(path: '/item/{id}.{_format}', name: 'doc_tree_item_view', defaults: ['_format' => 'html'], methods: ['GET'])]
    #[Route(path: '/item/{id}/{assocGroup}.{_format}', name: 'doc_tree_item_view_ag', defaults: ['_format' => 'html'], methods: ['GET'])]
    public function viewItem(LsItem $lsItem): Response
    {
        return $this->redirectToFrameworkItemEditor($lsItem);
    }

    /**
     * Create a response with a CFDocument.
     */
    protected function respondWithDocumentById(Request $request, int $id): Response
    {
        // in this case it has to be a document on this OpenSALT instantiation
        $newDoc = $this->docRepository->find($id);
        if (empty($newDoc)) {
            // if document not found, error
            return new Response('Document not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->export($request, $newDoc);
    }

    /**
     * Create a response with a CFDocument.
     */
    protected function respondWithDocumentByIdentifier(Request $request, string $identifier, LsDoc $lsDoc): Response
    {
        $newDoc = $this->docRepository->findOneBy(['identifier' => $identifier]);
        if (null !== $newDoc) {
            return $this->export($request, $newDoc);
        }

        // otherwise look in this doc's externalDocs
        // We could store, and check here, a global table of external documents that we could index by identifiers, instead of using document-specific associated docs. But it's not completely clear that would be an improvement.
        $externalDocs = $lsDoc->getExternalDocs();
        if (!empty($externalDocs[$identifier])) {
            // if we found it, load it, noting that we don't have to save a record of it in externalDocs (since it's already there)
            $response = $this->exportExternalDocument($externalDocs[$identifier]['url'], null);
            if (Response::HTTP_OK !== $response->getStatusCode()) {
                return $response;
            }

            $content = $response->getContent();
            $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            $lastModified = new \DateTime($json['CFDocument']['lastChangeDateTime'], new \DateTimeZone('UTC'));

            $response->setEtag(md5($lastModified->format('U.u').self::ETAG_SEED), true);
            $response->setLastModified($lastModified);
            $response->setMaxAge(0);
            $response->setSharedMaxAge(0);
            $response->setExpires(\DateTime::createFromFormat('U', $lastModified->format('U'))->sub(new \DateInterval('PT1S')));
            $response->setPublic();
            $response->headers->addCacheControlDirective('must-revalidate');

            if ($response->isNotModified($request)) {
                return $response;
            }

            return $response;
        }

        // if not found in externalDocs, error
        return new Response('Document not found.', Response::HTTP_NOT_FOUND);
    }

    protected function addExternalDocumentToDoc(string $url, LsDoc $lsDoc, string $document): void
    {
        $doc = json_decode($document, false, 512, JSON_THROW_ON_ERROR);
        $title = $doc->CFDocument->title;
        $identifier = $doc->CFDocument->identifier;

        // if we found the identifier and title, save the ad
        if (!empty($identifier) && !empty($title)) {
            // see if the doc is already there; if so, we don't want to change the "autoLoad" parameter, but we should still update the title/url if necessary
            $externalDocs = $lsDoc->getExternalDocs();

            $autoLoad = 'false';
            if (!empty($externalDocs[$identifier])) {
                $autoLoad = $externalDocs[$identifier]['autoLoad'];
            }

            // if this is a new doc or anything has changed, save it
            if (empty($externalDocs[$identifier])
                || $externalDocs[$identifier]['autoLoad'] !== $autoLoad
                || $externalDocs[$identifier]['url'] !== $url
                || $externalDocs[$identifier]['title'] !== $title
            ) {
                $command = new AddExternalDocCommand($lsDoc, $identifier, $autoLoad, $url, $title);
                $this->sendCommand($command);
            }
        }
    }

    protected function fetchExternalDocument(string $url): string
    {
        $headers = [
            'Accept' => 'application/vnd.opensalt+json, application/json;q=0.9, text/plain;q=0.2, */*;q=0.1',
        ];
        $headers = $this->addAuthentication($url, $headers);

        $jsonClient = new Client();
        $extDoc = $jsonClient->request(
            'GET',
            $url,
            [
                'timeout' => 60,
                'headers' => $headers,
                'http_errors' => true,
            ]
        );

        return $extDoc->getBody()->getContents();
    }

    protected function addAuthentication(string $url, array $headers): array
    {
        // Check for CASE Network urls
        if ($this->isCaseNetworkUrl($url)) {
            return array_merge([
                'Authorization' => 'Bearer '.$this->retrieveCaseNetworkBearerToken(),
            ], $headers);
        }

        return $headers;
    }

}
