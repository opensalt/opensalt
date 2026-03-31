<?php

declare(strict_types=1);

namespace App\VectorSearch\Controller;

use App\Entity\Framework\LsItemKind;
use App\VectorSearch\Service\VectorSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class VectorSearchApiController extends AbstractController
{
    public function __construct(
        private readonly VectorSearchService $vectorSearchService,
    ) {
    }

    #[Route('/api/vector-search', name: 'api_vector_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('query');
        $limit = (int) $request->query->get('limit', 10);
        $frameworkId = $request->query->has('framework') ? (int) $request->query->get('framework') : null;
        $leafOnly = $request->query->getBoolean('leaf_only', false);
        $kindRaw = $request->query->get('kind');
        $kind = null !== $kindRaw && '' !== $kindRaw && is_numeric($kindRaw)
            ? (LsItemKind::tryFrom((int) $kindRaw)?->value)
            : null;

        if (empty($query)) {
            return $this->json([
                'error' => 'Query parameter is required',
            ], 400);
        }

        $results = $this->vectorSearchService->searchByQuery($query, $limit, $frameworkId, $leafOnly, $kind);

        $responseData = [
            'query' => $query,
            'limit' => $limit,
            'framework_id' => $frameworkId,
            'leaf_only' => $leafOnly,
            'kind' => $kind,
            'count' => count($results),
            'results' => array_map(fn ($result) => [
                'item_identifier' => $result['lsItem']->getIdentifier(),
                'full_statement' => $result['lsItem']->getFullStatement(),
                'human_coding_scheme' => $result['lsItem']->getHumanCodingScheme(),
                'abbreviated_statement' => $result['lsItem']->getAbbreviatedStatement(),
                'relevance' => $result['similarity'],
                'kind' => $result['lsItem']->getDiscriminator(),
                'embedding_id' => $result['embedding']->getId(),
                'is_leaf_node' => $result['embedding']->isLeafNode(),
                'source_hierarchy_updated_at' => $result['embedding']->getSourceHierarchyUpdatedAt()?->format('Y-m-d H:i:s'),
                'created_at' => $result['embedding']->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $results),
        ];

        return $this->json($responseData);
    }

    #[Route('/api/vector-search/stats', name: 'api_vector_search_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $vectorCount = $this->vectorSearchService->getVectorCount();

        return $this->json([
            'vector_count' => $vectorCount,
        ]);
    }
}
