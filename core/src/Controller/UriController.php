<?php

namespace App\Controller;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsItemRepository;
use App\Service\IdentifiableObjectHelper;
use App\Service\UriGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\WebLink\Link;

class UriController extends AbstractController
{
    public function __construct(
        private readonly IdentifiableObjectHelper $objectHelper,
        private readonly SerializerInterface $symfonySerializer,
        private readonly UriGenerator $uriGenerator,
        private readonly LsAssociationRepository $associationRepository,
        private readonly LsItemRepository $itemRepository,
    ) {
    }

    #[Route(path: '/uri/', name: 'uri_lookup_empty', defaults: ['_format' => 'html'], methods: ['GET'])]
    public function findEmptyUri(Request $request): Response
    {
        $this->determineRequestFormat($request, null);

        if (in_array($request->getRequestFormat(), ['json', 'jsonld', 'opensalt'])) {
            return new JsonResponse([
                'error' => 'Identifier not found',
            ], Response::HTTP_NOT_FOUND, [
                'Content-Type' => $request->getMimeType($request->getRequestFormat()),
            ]);
        }

        return $this->render('uri/no_uri.html.twig', ['uri' => null], new Response('', Response::HTTP_NOT_FOUND));
    }

    #[Route(path: '/uri/{uri}.{_format}', name: 'uri_lookup', defaults: ['_format' => null], methods: ['GET'])]
    public function findUri(Request $request, string $uri, ?string $_format): Response
    {
        if ($request->isXmlHttpRequest()) {
            $_format = 'json';
        }
        $this->determineRequestFormat($request, $_format);

        $originalUri = $uri;
        $isPackage = false;
        if (str_starts_with($uri, UriGenerator::PACKAGE_PREFIX)) {
            $isPackage = true;
            $uri = preg_replace('/^'.UriGenerator::PACKAGE_PREFIX.'/', '', $uri);
        }

        $obj = $this->objectHelper->findObjectByIdentifier($uri);
        if (null === $obj) {
            return $this->generateNotFoundResponse($request, $uri);
        }

        if ('tree' === $request->getRequestFormat()) {
            switch ($obj::class) {
                case LsDoc::class:
                    return $this->redirectToRoute('doc_tree_view', ['slug' => $obj->getId()]);

                case LsItem::class:
                    return $this->redirectToRoute('doc_tree_item_view', ['id' => $obj->getId()]);
            }

            $request->setRequestFormat('html');
        }

        $this->addLinksToHeader($request, $originalUri);
        $headers = $this->generateTcnHeaders($originalUri);

        // Send multiple choice (300) response if no Accept header
        $accept = $request->headers->get('Accept');
        if (empty($accept)) {
            return $this->generateMultipleChoiceResponse($originalUri, $headers);
        }

        // Return an is not modified response if appropriate
        $lastModified = $obj->getUpdatedAt();
        $response = $this->generateBaseResponse($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $headers['TCN'] = 'choice';
        $response->headers->add($headers);

        if ($obj instanceof LsItem) {
            $type = $obj->getItemType()?->getTitle();
            if (str_starts_with($type ?? '', 'Credential -') && in_array($request->getRequestFormat(), ['html', 'jsonld'])) {
                return $this->renderCredentialView($obj, $request, $response);
            }
        }

        $className = $isPackage ? 'CFPackage' : substr(strrchr($obj::class, '\\'), 1);
        $groups = ['default', $className];
        if ('opensalt' === $request->getRequestFormat()) {
            $groups[] = 'opensalt';
        }
        $serialized = $this->symfonySerializer->serialize($obj, 'json', [
            'groups' => $groups,
            'json_encode_options' => \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION,
            'case-json-ld' => ('jsonld' === $request->getRequestFormat()) ? 'v1p0' : null,
            'add-case-context' => ('jsonld' === $request->getRequestFormat()) ? 'v1p0' : null,
            'generate-package' => $isPackage ? 'v1p0' : null,
        ]);

        // Found -- Display
        if ('html' === $request->getRequestFormat()) {
            return $this->render('uri/found_uri.html.twig', [
                'obj' => $obj,
                'class' => $className,
                'isPackage' => $isPackage,
                'serialized' => json_decode($serialized, true, 512, JSON_THROW_ON_ERROR),
            ], $response);
        }

        if ($request->headers->has('x-opensalt')) {
            $request->setRequestFormat('json');
            $response->headers->set('X-OpenSALT-Response', 'requested');
        }
        $response->setContent($serialized);
        $response->headers->set('Content-Type', $request->getMimeType($request->getRequestFormat()));

        return $response;
    }

    private function determineRequestFormat(Request $request, ?string $_format): void
    {
        if ($request->headers->has('x-opensalt')) {
            $request->setRequestFormat('opensalt');

            return;
        }

        if ('tree' === $_format || 'tree' === $request->query->get('display')) {
            $request->setRequestFormat('tree');

            return;
        }

        $allowedFormats = [
            'application/vnd.opensalt+json' => 'opensalt',
            'application/json' => 'json',
            'application/ld+json' => 'jsonld',
            'text/html' => 'html',
        ];

        if (in_array($_format, $allowedFormats, true)) {
            $request->setRequestFormat($_format);

            return;
        }

        $useFormat = 'json';
        $quality = 0.0;

        $accept = AcceptHeader::fromString($request->headers->get('Accept'));
        $contentTypes = $accept->all();
        foreach ($contentTypes as $contentType) {
            $tryFormat = $request->getFormat($contentType);
            if (in_array($tryFormat, $allowedFormats, true)) {
                $useFormat = $tryFormat;
                $quality = $accept->get($contentType)?->getQuality() ?? 0.0;

                break;
            }
        }

        foreach ($allowedFormats as $contentType => $format) {
            $q = $accept->get($contentType)?->getQuality() ?? 0.0;
            if ($quality < $q) {
                $useFormat = $format;
                $quality = $q;
            }
        }

        $request->setRequestFormat($useFormat);
    }

    protected function generateBaseResponse(\DateTimeInterface $lastModified): Response
    {
        return new Response();
    }

    protected function addLinksToHeader(Request $request, string $originalUri): void
    {
        $this->addLink(
            $request,
            (new Link('canonical', "/uri/{$originalUri}"))
        );
        $this->addLink(
            $request,
            (new Link('alternate', "/uri/{$originalUri}.json"))->withAttribute('type', 'application/json')
        );
        $this->addLink(
            $request,
            (new Link('alternate', "/uri/{$originalUri}.jsonld"))->withAttribute('type', 'application/ld+json')
        );
        $this->addLink(
            $request,
            (new Link('alternate', "/uri/{$originalUri}.html"))->withAttribute('type', 'text/html')
        );
    }

    protected function generateTcnHeaders(string $originalUri): array
    {
        // RFC 2295
        return [
            'TCN' => 'list',
            'Vary' => 'negotiate, accept',
            'Alternates' => implode(
                ', ',
                [
                    "{\"/uri/{$originalUri}.html\" 0.9 {type text/html}}",
                    "{\"/uri/{$originalUri}.json\" 1.0 {type application/json}}",
                    "{\"/uri/{$originalUri}.jsonld\" 1.0 {type application/ld+json}}",
                ]
            ),
        ];
    }

    protected function generateMultipleChoiceResponse(string $originalUri, array $headers): Response
    {
        $content = <<<"xENDx"
<h2>Multiple Choices:</h2>
<ul>
<li><a href="/uri/{$originalUri}.html">HTML</a></li>
<li><a href="/uri/{$originalUri}.json">JSON</a></li>
<li><a href="/uri/{$originalUri}.jsonld">JSON-LD</a></li>
</ul>

xENDx;

        return new Response($content, Response::HTTP_MULTIPLE_CHOICES, $headers);
    }

    protected function generateNotFoundResponse(Request $request, string $uri): Response
    {
        if ('html' === $request->getRequestFormat()) {
            return $this->render(
                'uri/uri_not_found.html.twig',
                ['uri' => $uri],
                new Response('', Response::HTTP_NOT_FOUND)
            );
        }

        throw new NotFoundHttpException(sprintf('Object with identifier "%s" was not found', $uri));
    }

    private function renderCredentialView(LsItem $obj, Request $request, Response $response): Response
    {
        $response->setPublic();

        $allAssociations = $this->associationRepository->findAllAssociationsForAsSplitArray($obj->getIdentifier());
        $associations = $allAssociations['associations'];
        $img = null;
        $criteria = [];
        $alignments = [];
        foreach ($associations as $association) {
            $destination = $association->getDestination();

            if ($destination instanceof LsDoc) {
                continue;
            }

            switch ($association->getType()) {
                case LsAssociation::PRECEDES:
                //case LsAssociation::CHILD_OF:
                    break;

                case LsAssociation::EXEMPLAR:
                    if (is_string($destination) && (str_ends_with($destination, '.png') || str_ends_with($destination, '.svg'))) {
                        $img = $destination;
                    }
                    break;

                default:
                    if ($destination instanceof LsItem) {
                        $alignments[$destination->getIdentifier()] = $destination;
                    }
                    break;
            }
        }

        $associations = $allAssociations['inverseAssociations'];
        foreach ($associations as $association) {
            $origin = $association->getOrigin();
            if (is_string($origin)) {
                $origin = $this->itemRepository->findOneBy(['identifier' => $association->getOriginNodeIdentifier()]);
            }
            if (!$origin instanceof LsItem) {
                continue;
            }

            switch ($association->getType()) {
                case LsAssociation::EXEMPLAR:
                //case LsAssociation::CHILD_OF:
                case LsAssociation::EXACT_MATCH_OF:
                    break;

                case LsAssociation::PRECEDES:
                    $criteria[$origin->getIdentifier()] = $origin;
                    break;

                default:
                    $alignments[$origin->getIdentifier()] = $origin;
                    break;
            }
        }

        // If we have an alignment in the criteria, remove it from the alignments
        foreach (array_keys($criteria) as $key) {
            if (array_key_exists($key, $alignments)) {
                unset($alignments[$key]);
            }
        }

        if ('jsonld' === $request->getRequestFormat()) {
            $achievementType = preg_replace('/Credential - /', '', $obj->getItemType()?->getTitle() ?? '');

            $credential = [
                '@context' => [
                    'https://www.w3.org/2018/credentials/v1',
                    'https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json',
                ],
                'id' => $this->uriGenerator->getUri($obj),
                'type' => ['Achievement'],
                'achievementType' => [$achievementType],
                'name' => $obj->getAbbreviatedStatement() ?? $obj->getFullStatement(),
                'description' => $obj->getFullStatement(),
                'humanCode' => $obj->getHumanCodingScheme() ?? '',
                'criteria' => [
                    'narrative' => null,
                    'id' => $this->uriGenerator->getUri($obj).'.html',
                ],
                'alignment' => [],
                'image' => [
                    'id' => $img ?? '',
                    'type' => 'Image',
                ],
            ];

            $narrative = [];
            foreach ($criteria as $criterion) {
                $narrative[] = '- '.($criterion->getAbbreviatedStatement() ?? $criterion->getFullStatement());
            }
            $credential['criteria']['narrative'] = implode("\n", $narrative);

            if ('' === $credential['criteria']['narrative']) {
                unset($credential['criteria']['narrative']);
            }

            foreach ($alignments as $alignment) {
                $credential['alignment'][] = [
                    'type' => 'Alignment',
                    // 'targetCode' => $alignment->getIdentifier(),
                    // 'targetDescription' => $alignment->getFullStatement(),
                    'targetName' => $alignment->getAbbreviatedStatement() ?? $alignment->getFullStatement(),
                    // 'targetFramework' => $alignment->getFramework(),
                    // 'targetType' => $alignment->getItemType()?->getTitle() ?? '',
                    'targetType' => 'CFItem',
                    'targetUrl' => $this->uriGenerator->getUri($alignment),
                ];
            }
            if (0 === count($credential['alignment'])) {
                unset($credential['alignment']);
            }

            if ('' === $credential['humanCode']) {
                unset($credential['humanCode']);
            }

            if ('' === $credential['image']['id']) {
                unset($credential['image']);
            }

            return new JsonResponse($credential, Response::HTTP_OK);
        }

        return $this->render('uri/credential_view.html.twig', [
            'obj' => $obj,
            'img' => $img,
            'criteria' => $criteria,
            'alignments' => $alignments,
            'associationRepo' => $this->associationRepository,
            'itemRepo' => $this->itemRepository,
        ], $response);
    }
}
