<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsDocRepository;
use App\Repository\Framework\LsItemRepository;
use App\Security\Permission;
use App\Service\Api1Uris;
use App\Service\IdentifiableObjectHelper;
use App\Service\UriGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\WebLink\Link;

class UriController extends AbstractController
{
    public function __construct(
        private readonly IdentifiableObjectHelper $objectHelper,
        private readonly SerializerInterface $symfonySerializer,
        private readonly NormalizerInterface $normalizer,
        private readonly UriGenerator $uriGenerator,
        private readonly Api1Uris $api1Uris,
        private readonly LsAssociationRepository $associationRepository,
        private readonly LsItemRepository $itemRepository,
        private readonly LsDocRepository $docRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly EntityManagerInterface $entityManager,
        private readonly Stopwatch $stopwatch,
        private readonly SerializerInterface $serializer,
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
    public function findUriSingleIdentifier(Request $request, string $uri, ?string $_format = null): Response
    {
        return $this->findUri($request, $uri, null, $_format);
    }

    #[Route(path: '/uri/{framework}/{uri}.{_format}', name: 'uri_lookup_framework', defaults: ['_format' => null], methods: ['GET'])]
    public function findUri(Request $request, string $uri, ?string $framework = null, ?string $_format = null): Response
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

        $obj = $this->objectHelper->findObjectByIdentifier($uri, $framework);
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
        if (null === $accept) {
            return $this->generateMultipleChoiceResponse($originalUri, $headers);
        }

        // Return an is not modified response if appropriate
        $lastModified = $obj->getUpdatedAt();
        $response = $this->generateBaseResponse($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->headers->set('Content-Type', $request->getMimeType($request->getRequestFormat()));

        $headers['TCN'] = 'choice';
        $response->headers->add($headers);

        if ($request->headers->has('x-opensalt')) {
            $response->headers->set('X-OpenSALT-Response', 'requested');
        }

        if ($obj instanceof LsItem) {
            $type = $obj->getItemType()?->getTitle();
            if ((LsItem::TYPES['credential'] === $obj->getDiscriminator() || str_starts_with($type ?? '', 'Credential - '))
                && in_array($request->getRequestFormat(), ['html', 'jsonld'])) {
                return $this->renderCredentialView($obj, $request, $response);
            }
        }

        $className = $isPackage ? 'CFPackage' : substr(strrchr($obj::class, '\\') ?: '', 1);
        $groups = ['default', 'CASE-1.1', $className];
        if ('opensalt' === $request->getRequestFormat()) {
            $groups[] = 'opensalt';
        }

        $context = [
            'groups' => $groups,
            'json_encode_options' => \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION,
            'case-json-ld' => ('jsonld' === $request->getRequestFormat()) ? 'v1p0' : null,
            'add-case-context' => ('jsonld' === $request->getRequestFormat()) ? 'v1p0' : null,
            'generate-package' => $isPackage ? 'v1p0' : null,
        ];

        if ($isPackage && 'html' !== $request->getRequestFormat()) {
            return $this->generatePackageResponse($request, $response, $obj, $context);
        }

        $serializationFormat = match ($request->getRequestFormat()) {
            'json', 'jsonld', 'opensalt', 'ndjson', 'html' => 'json',
            default => $request->getRequestFormat(),
        };
        $serialized = $this->symfonySerializer->serialize($obj, $serializationFormat, $context);

        // Found -- Display
        if ('html' === $request->getRequestFormat()) {
            return $this->render('uri/found_uri.html.twig', [
                'obj' => $obj,
                'class' => $className,
                'isPackage' => $isPackage,
                'serialized' => json_decode($serialized, true, 512, JSON_THROW_ON_ERROR),
            ], $response);
        }

        $response->setContent($serialized);

        return $response;
    }

    private function determineRequestFormat(Request $request, ?string $_format = null): void
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
            'text/csv' => 'csv',
            'application/x-ndjson' => 'ndjson',
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
            $tryFormat = $request->getFormat($contentType->getValue());
            if (in_array($tryFormat, $allowedFormats, true)) {
                $useFormat = $tryFormat;
                $quality = $accept->get($contentType->getValue())?->getQuality() ?? 0.0;

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
            new Link('canonical', '/uri/' . $originalUri)
        );
        $this->addLink(
            $request,
            new Link('alternate', sprintf('/uri/%s.json', $originalUri))->withAttribute('type', 'application/json')
        );
        $this->addLink(
            $request,
            new Link('alternate', sprintf('/uri/%s.jsonld', $originalUri))->withAttribute('type', 'application/ld+json')
        );
        $this->addLink(
            $request,
            new Link('alternate', sprintf('/uri/%s.html', $originalUri))->withAttribute('type', 'text/html')
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
                    sprintf('{"/uri/%s.html" 0.9 {type text/html}}', $originalUri),
                    sprintf('{"/uri/%s.json" 1.0 {type application/json}}', $originalUri),
                    sprintf('{"/uri/%s.jsonld" 1.0 {type application/ld+json}}', $originalUri),
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

        $credential = $obj->getExtensionProperty('ob3') ?? ($obj->getExtraProperty('extendedItem') ?? [])['ob3'] ?? 'null';
        if (is_string($credential)) {
            $credential = json5_decode($credential, true);
        }

        if (null === $credential) {
            $credential = [
                'type' => ['Achievement'],
                'achievementType' => null,
                'name' => $obj->getAbbreviatedStatement() ?? $obj->getFullStatement(),
                'description' => $obj->getFullStatement(),
                'humanCode' => $obj->getHumanCodingScheme() ?? '',
                'criteria' => [
                    'narrative' => '',
                    'id' => $this->uriGenerator->getUri($obj).'.html',
                ],
                'alignment' => [],
                'image' => [
                    'id' => '',
                    'type' => 'Image',
                ],
            ];
        }

        $iri = $this->api1Uris->getUri($obj);
        $credential = array_merge(['@context' => [], 'id' => $iri], $credential);

        $img = ('' !== ($credential['image']['id'] ?? '')) ? $credential['image']['id'] : '';

        $allAssociations = $this->associationRepository->findAllAssociationsForAsSplitArray($obj->getIdentifier());
        $associations = $allAssociations['associations'];
        $criteria = [];
        $alignments = [];
        foreach ($associations as $association) {
            $destination = $association->getDestination();

            if ($destination instanceof LsDoc) {
                continue;
            }

            switch ($association->getType()) {
                case LsAssociation::PRECEDES:
                    // case LsAssociation::CHILD_OF:
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

        $credential['image']['id'] = $img;

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
                    // case LsAssociation::CHILD_OF:
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

        if (null === $credential['achievementType']) {
            $achievementType = preg_replace('/Credential - /', '', $obj->getItemType()?->getTitle() ?? 'Credential - Achievement');
            if (!in_array($achievementType, [
                'Achievement',
                'ApprenticeshipCertificate',
                'Assessment',
                'Assignment',
                'AssociateDegree',
                'Award',
                'Badge',
                'BachelorDegree',
                'Certificate',
                'CertificateOfCompletion',
                'Certification',
                'CommunityService',
                'Competency',
                'Course',
                'CoCurricular',
                'Degree',
                'Diploma',
                'DoctoralDegree',
                'Fieldwork',
                'GeneralEducationDevelopment',
                'JourneymanCertificate',
                'LearningProgram',
                'License',
                'Membership',
                'ProfessionalDoctorate',
                'QualityAssuranceCredential',
                'MasterCertificate',
                'MasterDegree',
                'MicroCredential',
                'ResearchDoctorate',
                'SecondarySchoolDiploma',
            ], true)) {
                $achievementType = 'ext:'.$achievementType;
            }

            $credential['achievementType'] = $achievementType;
        }

        if ('jsonld' === $request->getRequestFormat()) {
            $narrative = [];
            foreach ($criteria as $criterion) {
                $narrative[] = '- '.($criterion->getAbbreviatedStatement() ?? $criterion->getFullStatement());
            }
            if ('' !== ($credential['criteria']['narrative'] ?? '')) {
                $credential['criteria']['narrative'] .= "\n\n";
            }
            $credential['criteria']['narrative'] .= implode("\n", $narrative);

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
            if ([] === ($credential['alignment'] ?? 'X')) {
                unset($credential['alignment']);
            }

            if ('' === ($credential['humanCode'] ?? 'X')) {
                unset($credential['humanCode']);
            }

            if ('' === ($credential['image']['id'] ?? 'X')) {
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
            'credential' => $credential,
        ], $response);
    }

    private function generatePackageResponse(Request $request, Response $originalResponse, mixed $obj, array $context): Response
    {
        set_time_limit(60);

        $_format = $request->getRequestFormat();

        $jsonLd = $context['case-json-ld'] ?? null;
        $addContext = (null !== $jsonLd) ? ($context['add-case-context'] ?? null) : null;
        unset($context['add-case-context'], $context['generate-package']);
        $context['no-association-links'] = true;
        $context['groups'][] = 'CASE-1.1';
        if (in_array($_format, ['ndjson', 'csv'])) {
            $context['add-case-type'] = true;
            $context['no-case-link-uri-type'] = true;
            $context['case-json-ld'] = true;
            $jsonLd = true;
            $addContext = null;
            $context['groups'][] = 'opensalt';
        }

        $context['useFormat'] = 'json';
        if ('csv' === $_format) {
            $context['useFormat'] = 'csv';
        }

        $headers = [];
        foreach ($originalResponse->headers->getIterator() as $key => $value) {
            /** @psalm-var array<array-key, string>|string $value */
            $headers[$key] = $value;
        }

        $itemCallback = function () use ($obj, $context): \Generator {
            $cnt = -1;
            $start = 0;
            $limit = 2000;
            while (0 !== $cnt) {
                $this->stopwatch->start('fetchItems');
                $cnt = 0;
                $last = 0;
                $items = $this->docRepository->findAllItemsForCFPackage($obj, Query::HYDRATE_OBJECT, $start, $limit);
                foreach ($items as $key => $item) {
                    ++$cnt;
                    $last = $item->getId();
                    $this->entityManager->detach($item);
                    yield $this->normalizer->normalize($item, 'json', $context);
                    unset($items[$key]);
                }
                $start = $last;
                $this->stopwatch->stop('fetchItems');
            }
        };

        $associationCallback = function () use ($obj, $context): \Generator {
            $cnt = -1;
            $start = 0;
            $limit = 20000;
            while (0 !== $cnt) {
                $this->stopwatch->start('fetchAssociations');
                $cnt = 0;
                $last = 0;
                /** @var iterable<LsAssociation> $items */
                $items = $this->docRepository->findAllAssociationsIterator($obj, Query::HYDRATE_OBJECT, $start, $limit);
                foreach ($items as $item) {
                    $this->entityManager->detach($item);
                    ++$cnt;
                    $last = $item->getId();
                    if (!$this->canListDocument($item, 'origin')) {
                        // Remove associations to frameworks one can't normally see
                        // unset($items[$key]);
                        continue;
                    }
                    if (!$this->canListDocument($item, 'destination')) {
                        // Remove associations to frameworks one can't normally see
                        // unset($items[$key]);
                        continue;
                    }
                    yield $this->normalizer->normalize($item, 'json', $context);
                    // unset($items[$key]);
                }
                $start = $last;
                $this->stopwatch->stop('fetchAssociations');
            }
        };

        $conceptCallback = function () use ($obj, $context): \Generator {
            $items = $this->docRepository->findAllUsedConcepts($obj, Query::HYDRATE_OBJECT);
            foreach ($items as $key => $item) {
                yield $this->normalizer->normalize($item, 'json', $context);
                unset($items[$key]);
            }
        };

        $subjectCallback = function () use ($obj, $context): \Generator {
            $items = $obj->getSubjects();
            foreach ($items as $key => $item) {
                yield $this->normalizer->normalize($item, 'json', $context);
                unset($items[$key]);
            }
        };

        $licenseCallback = function () use ($obj, $context): \Generator {
            $items = $this->docRepository->findAllUsedLicences($obj, Query::HYDRATE_OBJECT);
            foreach ($items as $key => $item) {
                yield $this->normalizer->normalize($item, 'json', $context);
                unset($items[$key]);
            }
        };

        $itemTypeCallback = function () use ($obj, $context): \Generator {
            $items = $this->docRepository->findAllUsedItemTypes($obj, Query::HYDRATE_OBJECT);
            foreach ($items as $key => $item) {
                yield $this->normalizer->normalize($item, 'json', $context);
                unset($items[$key]);
            }
        };

        $groupCallback = function () use ($obj, $context): \Generator {
            $items = $this->docRepository->findAllUsedAssociationGroups($obj, Query::HYDRATE_OBJECT);
            foreach ($items as $key => $item) {
                yield $this->normalizer->normalize($item, 'json', $context);
                unset($items[$key]);
            }
        };

        $rubricCallback = function () use ($obj, $context): \Generator {
            $items = $this->docRepository->findAllUsedRubrics($obj, Query::HYDRATE_OBJECT);
            foreach ($items as $key => $item) {
                yield $this->normalizer->normalize($item, 'json', $context);
                unset($items[$key]);
            }
        };

        if (in_array($_format, ['ndjson', 'csv'])) {
            return new StreamedResponse(function () use ($obj, $itemCallback, $associationCallback, $conceptCallback, $subjectCallback, $licenseCallback, $itemTypeCallback, $groupCallback, $context): void {
                $context += [
                   'json_encode_options' => JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
                   'no_headers' => true,
                   'csv_end_of_line' => '',
                ];
                $eol = ('csv' === $context['useFormat']) ? '' : "\n";
                echo $this->serializer->serialize($obj, $context['useFormat'], $context).$eol;
                foreach ($itemCallback() as $item) {
                    echo $this->serializer->serialize($item, $context['useFormat'], $context).$eol;
                }
                foreach ($associationCallback() as $item) {
                    echo $this->serializer->serialize($item, $context['useFormat'], $context).$eol;
                }
                foreach ($conceptCallback() as $item) {
                    echo $this->serializer->serialize($item, $context['useFormat'], $context).$eol;
                }
                foreach ($subjectCallback() as $item) {
                    echo $this->serializer->serialize($item, $context['useFormat'], $context).$eol;
                }
                foreach ($licenseCallback() as $item) {
                    echo $this->serializer->serialize($item, $context['useFormat'], $context).$eol;
                }
                foreach ($itemTypeCallback() as $item) {
                    echo $this->serializer->serialize($item, $context['useFormat'], $context).$eol;
                }
                foreach ($groupCallback() as $item) {
                    echo $this->serializer->serialize($item, $context['useFormat'], $context).$eol;
                }

                // Put criteria and levels on their own lines
                $items = $this->docRepository->findAllUsedRubrics($obj, Query::HYDRATE_OBJECT);
                foreach ($items as $item) {
                    echo $this->serializer->serialize($item, $context['useFormat'], $context).$eol;
                    foreach ($item->getCriteria() as $criteria) {
                        echo $this->serializer->serialize($criteria, $context['useFormat'], $context).$eol;
                        foreach ($criteria->getLevels() as $level) {
                            echo $this->serializer->serialize($level, $context['useFormat'], $context).$eol;
                        }
                    }
                }
            }, $originalResponse->getStatusCode(), $headers);
        }

        $json = [];
        if (null !== $addContext) {
            $json['@context'] = 'https://purl.imsglobal.org/spec/case/v1p0/context/imscasev1p0_context_v1p0.jsonld';
            $json['uri'] = (null !== $jsonLd)
                ? $this->api1Uris->getUri($obj, 'api_v1p0_cfpackage')
                : null;
            $json['type'] = (null !== $jsonLd)
                ? 'CFPackage'
                : null;
        }
        $json = array_filter($json, static fn ($field): bool => null !== $field);

        $json += [
            'CFDocument' => $this->normalizer->normalize($obj, 'json', $context),
            'CFItems' => $itemCallback(),
            'CFAssociations' => $associationCallback(),
            'CFDefinitions' => [
                'CFConcepts' => $conceptCallback(),
                'CFSubjects' => $subjectCallback(),
                'CFLicenses' => $licenseCallback(),
                'CFItemTypes' => $itemTypeCallback(),
                'CFAssociationGroupings' => $groupCallback(),
            ],
            'CFRubrics' => $rubricCallback(),
            // 'extensions' => null,
        ];

        return new StreamedJsonResponse($json, $originalResponse->getStatusCode(), $headers);
    }

    protected function canListDocument(LsAssociation $obj, string $which): bool
    {
        $target = match ($which) {
            'origin' => $obj->getOrigin(),
            'destination' => $obj->getDestination(),
            default => throw new \InvalidArgumentException('Expected "origin" or "destination"'),
        };

        if (!is_object($target)) {
            return true;
        }

        $targetDoc = match (true) {
            $target instanceof LsDoc => $target,
            $target instanceof LsItem => $target->getLsDoc(),
        };

        if (LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT !== $targetDoc->getAdoptionStatus()) {
            return true;
        }

        if ($obj->getLsDoc()?->getId() === $targetDoc->getId()) {
            // Even if private draft, we can view if the targetDoc is the same as this one
            return true;
        }

        return $this->authorizationChecker->isGranted(Permission::FRAMEWORK_LIST, $targetDoc);
    }
}
