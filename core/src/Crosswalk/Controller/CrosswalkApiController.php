<?php

declare(strict_types=1);

namespace App\Crosswalk\Controller;

use App\Attribute\ReadOnlySession;
use App\Crosswalk\Entity\CrosswalkJob;
use App\Crosswalk\Message\CreateCrosswalkMessage;
use App\Crosswalk\Repository\CrosswalkJobRepository;
use App\Crosswalk\Service\CrosswalkService;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Security\Permission;
use App\VectorSearch\Service\VectorSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class CrosswalkApiController extends AbstractController
{
    public function __construct(
        private readonly VectorSearchService $vectorSearchService,
        private readonly CrosswalkService $crosswalkService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?CrosswalkJobRepository $jobRepository = null,
        private readonly ?MessageBusInterface $messageBus = null,
    ) {
    }

    #[Route('/api/vector-search/crosswalk/estimate', name: 'api_crosswalk_estimate', methods: ['GET'])]
    public function estimate(Request $request): JsonResponse
    {
        $originIdentifier = $request->query->get('origin');
        $destinationIdentifier = $request->query->get('destination');
        $threshold = (float) $request->query->get('threshold', 0.75);
        $originLeafOnly = filter_var($request->query->get('origin_leaf_only', false), \FILTER_VALIDATE_BOOL);
        $destinationLeafOnly = filter_var($request->query->get('destination_leaf_only', false), \FILTER_VALIDATE_BOOL);

        if (!$originIdentifier || !$destinationIdentifier) {
            return new JsonResponse(['error' => 'origin and destination parameters are required'], 400);
        }

        $originDoc = $this->entityManager->getRepository(LsDoc::class)->findOneBy(['identifier' => $originIdentifier]);
        $destinationDoc = $this->entityManager->getRepository(LsDoc::class)->findOneBy(['identifier' => $destinationIdentifier]);

        if (!$originDoc || !$destinationDoc) {
            return new JsonResponse(['error' => 'Framework not found'], 404);
        }

        if (!$this->isGranted(Permission::FRAMEWORK_VIEW, $originDoc) || !$this->isGranted(Permission::FRAMEWORK_VIEW, $destinationDoc)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $originId = (int) $originDoc->getId();
        $destinationId = (int) $destinationDoc->getId();

        $originItemsWithEmbeddings = $this->vectorSearchService->getVectorCountForFramework($originId, $originLeafOnly);
        $destinationItemsWithEmbeddings = $this->vectorSearchService->getVectorCountForFramework($destinationId, $destinationLeafOnly);

        if ($originLeafOnly) {
            $originItemsTotal = $this->crosswalkService->countLeafItems($originId);
        } else {
            $originItemsTotal = (int) $this->entityManager->getRepository(LsItem::class)->count(['lsDoc' => $originId]);
        }

        return new JsonResponse([
            'origin_framework_id' => $originId,
            'origin_framework_identifier' => (string) $originDoc->getIdentifier(),
            'destination_framework_id' => $destinationId,
            'destination_framework_identifier' => (string) $destinationDoc->getIdentifier(),
            'threshold' => $threshold,
            'origin_leaf_only' => $originLeafOnly,
            'destination_leaf_only' => $destinationLeafOnly,
            'origin_items_with_embeddings' => $originItemsWithEmbeddings,
            'origin_items_without_embeddings' => max(0, $originItemsTotal - $originItemsWithEmbeddings),
            'destination_items_with_embeddings' => $destinationItemsWithEmbeddings,
            'note' => 'Exact match count will be determined during processing',
        ]);
    }

    #[Route('/api/vector-search/crosswalk/match', name: 'api_crosswalk_match', methods: ['GET'])]
    public function match(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $itemIdentifier = $request->query->get('item');
        $frameworkIdentifier = $request->query->get('framework');
        $limit = max(1, min(20, (int) $request->query->get('limit', 5)));
        $leafOnly = filter_var($request->query->get('leaf_only', false), \FILTER_VALIDATE_BOOL);

        if (!$itemIdentifier || !$frameworkIdentifier) {
            return new JsonResponse(['error' => 'item and framework parameters are required'], 400);
        }

        $item = $this->entityManager->getRepository(LsItem::class)->findOneBy(['identifier' => $itemIdentifier]);
        $framework = $this->entityManager->getRepository(LsDoc::class)->findOneBy(['identifier' => $frameworkIdentifier]);

        $this->denyAccessUnlessGranted(Permission::FRAMEWORK_EDIT, $framework);

        if (!$item || !$framework) {
            return new JsonResponse(['error' => 'Item or framework not found'], 404);
        }

        if (!$this->isGranted(Permission::FRAMEWORK_VIEW, $framework)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $results = $this->vectorSearchService->searchByLsItem($item, $limit, (int) $framework->getId(), $leafOnly);

        return new JsonResponse([
            'item_identifier' => (string) $item->getIdentifier(),
            'framework_identifier' => (string) $framework->getIdentifier(),
            'count' => count($results),
            'results' => array_map(static fn (array $result): array => [
                'item_identifier' => (string) $result['lsItem']->getIdentifier(),
                'full_statement' => $result['lsItem']->getFullStatement(),
                'human_coding_scheme' => $result['lsItem']->getHumanCodingScheme(),
                'abbreviated_statement' => $result['lsItem']->getAbbreviatedStatement(),
                'relevance' => $result['similarity'],
                'kind' => $result['lsItem']->getDiscriminator(),
            ], $results),
        ]);
    }

    #[Route('/api/vector-search/crosswalk', name: 'api_crosswalk_create', methods: ['POST'])]
    #[ReadOnlySession]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $originIdentifier = $data['origin_identifier'] ?? null;
        $destinationIdentifier = $data['destination_identifier'] ?? null;
        $crosswalkIdentifier = $data['crosswalk_identifier'] ?? null;
        $threshold = $data['threshold'] ?? 0.75;
        $exactMatchThreshold = $data['exact_match_threshold'] ?? 0.90;
        $originLeafOnly = (bool) ($data['origin_leaf_only'] ?? false);
        $destinationLeafOnly = (bool) ($data['destination_leaf_only'] ?? false);

        if (!$originIdentifier || !$destinationIdentifier || !$crosswalkIdentifier) {
            return new JsonResponse(['error' => 'origin_identifier, destination_identifier, and crosswalk_identifier are required'], 400);
        }

        $originDoc = $this->entityManager->getRepository(LsDoc::class)->findOneBy(['identifier' => $originIdentifier]);
        $destinationDoc = $this->entityManager->getRepository(LsDoc::class)->findOneBy(['identifier' => $destinationIdentifier]);
        $crosswalkDoc = $this->entityManager->getRepository(LsDoc::class)->findOneBy(['identifier' => $crosswalkIdentifier]);

        if (!$originDoc || !$destinationDoc || !$crosswalkDoc) {
            return new JsonResponse(['error' => 'Framework not found'], 404);
        }

        if (!$this->isGranted(Permission::FRAMEWORK_VIEW, $originDoc) || !$this->isGranted(Permission::FRAMEWORK_VIEW, $destinationDoc)) {
            return new JsonResponse(['error' => 'Access denied to origin or destination framework'], 403);
        }
        if (!$this->isGranted(Permission::FRAMEWORK_EDIT, $crosswalkDoc)) {
            return new JsonResponse(['error' => 'Edit access denied for crosswalk framework'], 403);
        }

        $originId = (int) $originDoc->getId();
        $destinationId = (int) $destinationDoc->getId();
        $crosswalkId = (int) $crosswalkDoc->getId();

        $job = new CrosswalkJob(
            originFrameworkId: $originId,
            destinationFrameworkId: $destinationId,
            crosswalkFrameworkId: $crosswalkId,
            threshold: (float) $threshold,
            exactMatchThreshold: (float) $exactMatchThreshold,
        );

        $this->jobRepository->save($job);

        $this->messageBus->dispatch(new CreateCrosswalkMessage(
            jobId: (string) $job->id,
            originFrameworkId: $originId,
            destinationFrameworkId: $destinationId,
            crosswalkFrameworkId: $crosswalkId,
            threshold: (float) $threshold,
            exactMatchThreshold: (float) $exactMatchThreshold,
            originLeafOnly: $originLeafOnly,
            destinationLeafOnly: $destinationLeafOnly,
        ));

        return new JsonResponse([
            'job_id' => (string) $job->id,
            'status' => 'queued',
        ], 202);
    }

    #[Route('/api/vector-search/crosswalk/{jobId}', name: 'api_crosswalk_status', methods: ['GET'])]
    public function getJobStatus(string $jobId): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $job = $this->jobRepository->find($jobId);

        if (!$job) {
            return new JsonResponse(['error' => 'Job not found'], 404);
        }

        $crosswalkDoc = $this->entityManager->getRepository(LsDoc::class)->find($job->crosswalkFrameworkId);
        if (!$this->isGranted(Permission::FRAMEWORK_VIEW, $crosswalkDoc)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        return new JsonResponse([
            'job_id' => (string) $job->id,
            'status' => $job->status,
            'threshold' => $job->threshold,
            'exact_match_threshold' => $job->exactMatchThreshold,
            'progress' => [
                'total' => $job->totalItems,
                'processed' => $job->processedItems,
                'matched' => $job->matchedItems,
                'exact_match_items' => $job->exactMatchItems,
                'related_items' => $job->relatedItems,
                'skipped_no_embedding' => $job->skippedNoEmbedding,
                'skipped_below_threshold' => $job->skippedBelowThreshold,
                'failed' => $job->failedItems,
            ],
            'started_at' => $job->startedAt?->format('c'),
            'completed_at' => $job->completedAt?->format('c'),
            'error' => $job->errorMessage,
        ]);
    }

    #[Route('/api/vector-search/crosswalk/{jobId}', name: 'api_crosswalk_cancel', methods: ['DELETE'])]
    public function cancel(string $jobId): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $job = $this->jobRepository->find($jobId);

        if (!$job) {
            return new JsonResponse(['error' => 'Job not found'], 404);
        }

        $crosswalkDoc = $this->entityManager->getRepository(LsDoc::class)->find($job->crosswalkFrameworkId);
        if (!$this->isGranted(Permission::FRAMEWORK_EDIT, $crosswalkDoc)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $job->markCancelled();
        $this->jobRepository->save($job);

        return new JsonResponse([
            'job_id' => (string) $job->id,
            'status' => 'cancelled',
        ]);
    }
}
