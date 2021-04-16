<?php

namespace App\Controller;

use App\Service\IdentifiableObjectHelper;
use App\Service\UriGenerator;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\WebLink\Link;

class UriController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private IdentifiableObjectHelper $objectHelper,
        private string $assetsVersion,
    )
    {
    }

    /**
     * @Route("/uri/", methods={"GET"}, defaults={"_format"="html"}, name="uri_lookup_empty")
     */
    public function findEmptyUriAction(Request $request): Response
    {
        // No identifier passed on the URL
        if ('json' === $request->getRequestFormat()) {
            return new JsonResponse([
                'error' => 'Identifier not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->render('uri/no_uri.html.twig', ['uri' => null], new Response('', Response::HTTP_NOT_FOUND));
    }

    /**
     * @Route("/uri/{uri}.{_format}", methods={"GET"}, defaults={"_format"=null}, name="uri_lookup")
     */
    public function findUriAction(Request $request, string $uri, ?string $_format): Response
    {
        if ($request->isXmlHttpRequest()) {
            $_format = 'json';
        }
        $this->determineRequestFormat($request, $_format);

        $this->addLink($request, (new Link('canonical', "/uri/{$uri}")));
        $this->addLink($request, (new Link('alternate', "/uri/{$uri}.json"))->withAttribute('type', 'application/json'));
        $this->addLink($request, (new Link('alternate', "/uri/{$uri}.html"))->withAttribute('type', 'text/html'));

        $isPackage = false;
        if (str_starts_with($uri, UriGenerator::PACKAGE_PREFIX)) {
            $isPackage = true;
            $uri = preg_replace('/^'.UriGenerator::PACKAGE_PREFIX.'/', '', $uri);
        }

        $obj = $this->objectHelper->findObjectByIdentifier($uri);

        if (null === $obj) {
            if ('html' === $request->getRequestFormat()) {
                return $this->render('uri/uri_not_found.html.twig', ['uri' => $uri], new Response('', Response::HTTP_NOT_FOUND));
            }

            return new JsonResponse([
                'error' => sprintf('Object with identifier "%s" was not found', $uri),
            ], Response::HTTP_NOT_FOUND);
        }

        // RFC 2295
        $headers = [
            'TCN' => 'list',
            'Vary' => 'negotiate, accept',
            'Alternates' => join(', ', [
                "{\"/uri/{$uri}.html\" 0.9 {type text/html}}",
                "{\"/uri/{$uri}.json\" 1.0 {type application/json}}",
            ])
        ];

        $accept = $request->headers->get('Accept');
        if (empty($accept)) {
            $content = <<<"xENDx"
<h2>Multiple Choices:</h2>
<ul>
<li><a href="/uri/{$uri}.html">HTML</a></li>
<li><a href="/uri/{$uri}.json">JSON</a></li>
</ul>

xENDx;

            return new Response($content, Response::HTTP_MULTIPLE_CHOICES, $headers);
        }

        if ($isPackage && 'json' === $request->getRequestFormat()) {
            // Redirect to API for the package
            return $this->redirectToRoute('api_v1p0_cfpackage', ['id' => $uri]);
        }

        $lastModified = $obj->getUpdatedAt();
        $response = $this->generateBaseResponse($lastModified);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $headers['TCN'] = 'choice';
        $response->headers->add($headers);

        // Found -- Display
        $serializationContext = SerializationContext::create();
        $serializationGroups = ['Default', 'LsDoc', 'LsItem', 'LsAssociation'];
        $serializationContext->setGroups($serializationGroups);
        $serialized = $this->serializer->serialize(
            $obj,
            'json',
            $serializationContext
        );
        if ('html' === $request->getRequestFormat()) {
            $className = substr(strrchr(get_class($obj), '\\'), 1);

            return $this->render('uri/found_uri.html.twig', [
                'obj' => $obj,
                'class' => $className,
                'isPackage' => $isPackage,
                'serialized' => json_decode($serialized, true, 512, JSON_THROW_ON_ERROR),
            ], $response);
        }

        $response->setContent($serialized);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function determineRequestFormat(Request $request, ?string $_format, array $allowedFormats = ['json', 'html']): void
    {
        if (in_array($_format, $allowedFormats, true)) {
            $request->setRequestFormat($_format);

            return;
        }

        $useFormat = 'json';
        $quality = 0;

        $accept = AcceptHeader::fromString($request->headers->get('Accept'));
        $contentTypes = $accept->all();
        foreach ($contentTypes as $contentType) {
            $tryFormat = $request->getFormat($contentType);
            if (in_array($tryFormat, $allowedFormats, true)) {
                $useFormat = $tryFormat;
                $quality = $accept->get($contentType)->getQuality();

                break;
            }
        }

        $tryTypes = [
            'application/json' => 'json',
            'application/ld+json' => 'jsonld',
            'text/html' => 'html',
        ];
        foreach ($tryTypes as $contentType => $format) {
            if (!in_array($format, $allowedFormats, true)) {
                continue;
            }

            $q = $accept->get($contentType)->getQuality();
            if ($quality < $q) {
                $useFormat = $format;
                $quality = $q;
            }
        }

        $request->setRequestFormat($useFormat);
    }

    protected function generateBaseResponse(\DateTimeInterface $lastModified): Response
    {
        $response = new Response();

        $response->setEtag(md5($lastModified->format('U.u').$this->assetsVersion), true);
        $response->setLastModified($lastModified);
        $response->setMaxAge(60);
        $response->setSharedMaxAge(60);
        $response->setPublic();
        $response->setVary(['Accept', 'Accept-Language']);

        return $response;
    }
}
