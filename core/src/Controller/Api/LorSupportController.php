<?php

namespace App\Controller\Api;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsDocRepository;
use App\Repository\Framework\LsItemRepository;
use App\Service\LoggerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/lor")
 */
class LorSupportController extends AbstractController
{
    use LoggerTrait;

    public function __construct(
        private LsDocRepository $docRepository,
        private LsItemRepository $itemRepository,
        private string $assetsVersion,
    ) {
    }

    /**
     * @Route("/creators", methods={"GET"}, name="api_get_creators")
     */
    public function getCreators(Request $request): JsonResponse
    {
        // Get all creators for public documents
        $results = $this->docRepository->findAllNonPrivate();

        $creators = [];
        $lastModified = new \DateTime('now - 10 years');
        foreach ($results as $doc) {
            /* @var LsDoc $doc */
            $creator = $doc->getCreator();
            $creators[$creator] = $creator;
            if ($doc->getUpdatedAt() > $lastModified) {
                $lastModified = $doc->getUpdatedAt();
            }
        }

        $this->info('API: getCreators', []);

        $response = $this->generateBaseResponse($lastModified, count($creators));
        if ($response->isNotModified($request)) {
            return $response;
        }

        $creators = array_values($creators);
        sort($creators);
        $response->setData($creators);

        return $response;
    }

    /**
     * @Route("/frameworksByCreator/{creator}", methods={"GET"}, name="api_get_frameworks_by_creator")
     */
    public function getFrameworksByCreator(Request $request, string $creator): JsonResponse
    {
        $results = $this->docRepository->findNonPrivateByCreator(urldecode($creator));

        $docs = [];
        $lastModified = new \DateTime('now - 10 years');
        foreach ($results as $doc) {
            /* @var LsDoc $doc */
            $docs[] = [
                'identifier' => $doc->getIdentifier(),
                'title' => $doc->getTitle(),
            ];
            if ($doc->getUpdatedAt() > $lastModified) {
                $lastModified = $doc->getUpdatedAt();
            }
        }

        $this->info('API: getFrameworksByCreator', []);

        $response = $this->generateBaseResponse($lastModified, count($docs));
        if ($response->isNotModified($request)) {
            return $response;
        }

        usort($docs, function ($a, $b) {
            return strcmp($a['title'], $b['title']);
        });

        $response->setData($docs);

        return $response;
    }

    /**
     * @Route("/exactMatchIdentifiers/{identifier}", methods={"GET"}, name="api_get_exact_matches")
     */
    public function getMatches(Request $request, string $identifier): JsonResponse
    {
        $results = $this->itemRepository->findExactMatches($identifier);

        $items = [];
        $lastModified = new \DateTime('now - 10 years');
        foreach ($results as $item) {
            /* @var LsItem $item */
            $items[] = $item->getIdentifier();
            if ($item->getUpdatedAt() > $lastModified) {
                $lastModified = $item->getUpdatedAt();
            }
        }

        $this->info('API: getMatches', []);

        $response = $this->generateBaseResponse($lastModified, count($items));
        if ($response->isNotModified($request)) {
            return $response;
        }

        sort($items);

        $response->setData([$identifier => $items]);

        return $response;
    }

    protected function generateBaseResponse(\DateTimeInterface $lastModified, ?int $total = null): JsonResponse
    {
        $response = new JsonResponse();

        $response->setEtag(md5($lastModified->format('U.u').$this->assetsVersion), true);
        $response->setLastModified($lastModified);
        $response->setMaxAge(60);
        $response->setSharedMaxAge(60);
        $response->setPublic();

        if (null !== $total) {
            $response->headers->set('X-Total-Count', (string) $total);
        }

        return $response;
    }
}
