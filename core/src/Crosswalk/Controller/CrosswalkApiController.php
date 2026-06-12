<?php

declare(strict_types=1);

namespace App\Crosswalk\Controller;

use App\Crosswalk\Entity\CrosswalkJob;
use App\Crosswalk\Message\CreateCrosswalkMessage;
use App\Crosswalk\Repository\CrosswalkJobRepository;
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

        return new JsonResponse([
            'origin_framework_id' => $originId,
            'origin_framework_identifier' => (string) $originDoc->getIdentifier(),
            'destination_framework_id' => $destinationId,
            'destination_framework_identifier' => (string) $destinationDoc->getIdentifier(),
            'threshold' => $threshold,
            'origin_items_with_embeddings' => $this->vectorSearchService->getVectorCountForFramework($originId),
            'origin_items_without_embeddings' => max(0, $this->entityManager->getRepository(LsItem::class)->count(['lsDoc' => $originId]) - $this->vectorSearchService->getVectorCountForFramework($originId)),
            'destination_items_with_embeddings' => $this->vectorSearchService->getVectorCountForFramework($destinationId),
            'note' => 'Exact match count will be determined during processing',
        ]);
    }

    #[Route('/api/vector-search/crosswalk', name: 'api_crosswalk_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $originIdentifier = $data['origin_identifier'] ?? null;
        $destinationIdentifier = $data['destination_identifier'] ?? null;
        $crosswalkIdentifier = $data['crosswalk_identifier'] ?? null;
        $threshold = $data['threshold'] ?? 0.75;
        $exactMatchThreshold = $data['exact_match_threshold'] ?? 0.90;

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
