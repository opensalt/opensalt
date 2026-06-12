<?php

declare(strict_types=1);

namespace App\Crosswalk\Controller;

use App\Crosswalk\Entity\CrosswalkJob;
use App\Crosswalk\Message\CreateCrosswalkMessage;
use App\Crosswalk\Repository\CrosswalkJobRepository;
use App\Entity\Framework\LsDoc;
use App\VectorSearch\Service\VectorSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class CrosswalkApiController
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
        $originId = $request->query->getInt('origin');
        $destinationId = $request->query->getInt('destination');
        $threshold = (float) $request->query->get('threshold', 0.75);

        if (!$originId || !$destinationId) {
            return new JsonResponse(['error' => 'origin and destination parameters are required'], 400);
        }

        $originDoc = $this->entityManager->getRepository(LsDoc::class)->find($originId);
        $destinationDoc = $this->entityManager->getRepository(LsDoc::class)->find($destinationId);

        if (!$originDoc || !$destinationDoc) {
            return new JsonResponse(['error' => 'Framework not found'], 404);
        }

        return new JsonResponse([
            'origin_framework_id' => $originId,
            'destination_framework_id' => $destinationId,
            'threshold' => $threshold,
            'note' => 'Exact match count will be determined during processing',
        ]);
    }

    #[Route('/api/vector-search/crosswalk', name: 'api_crosswalk_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $originId = $data['origin_id'] ?? null;
        $destinationId = $data['destination_id'] ?? null;
        $crosswalkId = $data['crosswalk_id'] ?? null;
        $threshold = $data['threshold'] ?? 0.75;
        $exactMatchThreshold = $data['exact_match_threshold'] ?? 0.90;

        if (!$originId || !$destinationId) {
            return new JsonResponse(['error' => 'origin_id and destination_id are required'], 400);
        }

        $originDoc = $this->entityManager->getRepository(LsDoc::class)->find($originId);
        $destinationDoc = $this->entityManager->getRepository(LsDoc::class)->find($destinationId);

        if (!$originDoc || !$destinationDoc) {
            return new JsonResponse(['error' => 'Framework not found'], 404);
        }

        if (!$crosswalkId) {
            $crosswalkDoc = new LsDoc();
            $crosswalkDoc->setTitle(sprintf('%s ↔ %s Crosswalk', $originDoc->getTitle(), $destinationDoc->getTitle()));
            $crosswalkDoc->setCreator('Crosswalk');
            $this->entityManager->persist($crosswalkDoc);
            $this->entityManager->flush();
            $crosswalkId = (int) $crosswalkDoc->getId();
        }

        $crosswalkId = (int) $crosswalkId;

        $job = new CrosswalkJob(
            originFrameworkId: (int) $originId,
            destinationFrameworkId: (int) $destinationId,
            crosswalkFrameworkId: $crosswalkId,
            threshold: (float) $threshold,
            exactMatchThreshold: (float) $exactMatchThreshold,
        );

        $this->jobRepository->save($job);

        $this->messageBus->dispatch(new CreateCrosswalkMessage(
            jobId: $job->getId(),
            originFrameworkId: (int) $originId,
            destinationFrameworkId: (int) $destinationId,
            crosswalkFrameworkId: $crosswalkId,
            threshold: (float) $threshold,
            exactMatchThreshold: (float) $exactMatchThreshold,
        ));

        return new JsonResponse([
            'job_id' => $job->getId(),
            'status' => 'queued',
        ], 202);
    }

    #[Route('/api/vector-search/crosswalk/{jobId}', name: 'api_crosswalk_status', methods: ['GET'])]
    public function getJobStatus(string $jobId): JsonResponse
    {
        $job = $this->jobRepository->find($jobId);

        if (!$job) {
            return new JsonResponse(['error' => 'Job not found'], 404);
        }

        return new JsonResponse([
            'job_id' => $job->getId(),
            'status' => $job->getStatus(),
            'threshold' => $job->getThreshold(),
            'exact_match_threshold' => $job->getExactMatchThreshold(),
            'progress' => [
                'total' => $job->getTotalItems(),
                'processed' => $job->getProcessedItems(),
                'matched' => $job->getMatchedItems(),
                'exact_match_items' => $job->getExactMatchItems(),
                'related_items' => $job->getRelatedItems(),
                'skipped' => $job->getSkippedNoEmbedding(),
                'failed' => $job->getFailedItems(),
            ],
            'started_at' => $job->getStartedAt()?->format('c'),
            'completed_at' => $job->getCompletedAt()?->format('c'),
            'error' => $job->getErrorMessage(),
        ]);
    }

    #[Route('/api/vector-search/crosswalk/{jobId}', name: 'api_crosswalk_cancel', methods: ['DELETE'])]
    public function cancel(string $jobId): JsonResponse
    {
        $job = $this->jobRepository->find($jobId);

        if (!$job) {
            return new JsonResponse(['error' => 'Job not found'], 404);
        }

        $job->markCancelled();
        $this->jobRepository->save($job);

        return new JsonResponse([
            'job_id' => $job->getId(),
            'status' => 'cancelled',
        ]);
    }
}
