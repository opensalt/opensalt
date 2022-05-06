<?php

namespace App\Controller;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Service\IdentifiableObjectHelper;
use App\Service\UriGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\WebLink\Link;

class UriController extends AbstractController
{
    public function __construct(
        private IdentifiableObjectHelper $objectHelper,
        private SerializerInterface $symfonySerializer,
    ) {
    }

    #[Route(path: '/uri/', methods: ['GET'], defaults: ['_format' => 'html'], name: 'uri_lookup_empty')]
    public function findEmptyUriAction(Request $request): Response
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

    #[Route(path: '/uri/{uri}.{_format}', methods: ['GET'], defaults: ['_format' => null], name: 'uri_lookup')]
    public function findUriAction(Request $request, string $uri, ?string $_format): Response
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

        $className = $isPackage ? 'CFPackage' : substr(strrchr($obj::class, '\\'), 1);
        $groups = ['default', $className];
        if ('opensalt' === $request->getRequestFormat()) {
            $groups[] = 'opensalt';
        }
        $serialized = $this->symfonySerializer->serialize($obj, 'json', [
            'groups' => $groups,
            'json_encode_options' => \JSON_UNESCAPED_SLASHES|\JSON_PRESERVE_ZERO_FRACTION,
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
}
