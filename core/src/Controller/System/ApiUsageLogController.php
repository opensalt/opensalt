<?php

declare(strict_types=1);

namespace App\Controller\System;

use App\Entity\System\ApiUsageLog;
use App\Repository\System\ApiUsageLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/system/api-usage')]
#[IsGranted('ROLE_SUPER_USER')]
final class ApiUsageLogController extends AbstractController
{
    public function __construct(
        private readonly ApiUsageLogRepository $repo,
    ) {
    }

    #[Route(path: '/', name: 'system_api_usage_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $limit = min(500, max(1, $request->query->getInt('limit', 50)));

        $userIdentifier = trim($request->query->getString('user', ''));
        $endpoint = trim($request->query->getString('endpoint', ''));

        $from = null;
        $fromParam = $request->query->getString('from', '');
        if ('' !== $fromParam) {
            $fromDt = \DateTimeImmutable::createFromFormat('Y-m-d', $fromParam) ?: null;
            if ($fromDt) {
                $from = $fromDt->setTime(0, 0, 0);
            }
        }

        $to = null;
        $toParam = $request->query->getString('to', '');
        if ('' !== $toParam) {
            $toDt = \DateTimeImmutable::createFromFormat('Y-m-d', $toParam) ?: null;
            if ($toDt) {
                $to = $toDt->setTime(23, 59, 59);
            }
        }

        // Cursor parameters (epoch seconds + id)
        $afterTs = $request->query->getInt('after_ts');
        $afterId = $request->query->getInt('after_id');
        $beforeTs = $request->query->getInt('before_ts');
        $beforeId = $request->query->getInt('before_id');

        $afterTsDt = (0 !== $afterTs) ? new \DateTimeImmutable('@'.$afterTs) : null;
        $afterIdInt = (0 !== $afterId) ? $afterId : null;
        $beforeTsDt = (0 !== $beforeTs) ? new \DateTimeImmutable('@'.$beforeTs) : null;
        $beforeIdInt = (0 !== $beforeId) ? $beforeId : null;

        // Fetch limit+1 to detect if there is a "next" page of older records
        $results = $this->repo->filterByCursor(
            '' !== $userIdentifier ? $userIdentifier : null,
            $from,
            $to,
            '' !== $endpoint ? $endpoint : null,
            $afterTsDt,
            $afterIdInt,
            $beforeTsDt,
            $beforeIdInt,
            $limit + 1,
        );

        $hasNext = \count($results) > $limit;
        $logs = \array_slice($results, 0, $limit);

        $hasPrev = (null !== $afterTsDt) || (null !== $beforeTsDt);

        // Build base query params for links
        $baseQs = [
            'user' => $userIdentifier,
            'endpoint' => $endpoint,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
            'limit' => $limit,
        ];

        $olderQs = null;
        $newerQs = null;

        if (!empty($logs)) {
            $first = $logs[0];
            $last = $logs[\count($logs) - 1];

            // "Older" moves towards older items (next page), by providing an 'after' cursor based on the last item
            $olderQs = \array_filter(\array_merge($baseQs, [
                'after_ts' => (int) $last->createdAt->format('U'),
                'after_id' => $last->id,
            ]), static fn ($v) => null !== $v && '' !== $v);

            // "Newer" moves towards newer items (prev page), by providing a 'before' cursor based on the first item
            $newerQs = \array_filter(\array_merge($baseQs, [
                'before_ts' => (int) $first->createdAt->format('U'),
                'before_id' => $first->id,
            ]), static fn ($v) => null !== $v && '' !== $v);
        }

        $identifiers = $this->repo->distinctUserIdentifiers();

        return $this->render('system_log/api_usage.html.twig', [
            'logs' => $logs,
            'limit' => $limit,
            'user' => $userIdentifier,
            'endpoint' => $endpoint,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
            'identifiers' => $identifiers,
            'has_next' => $hasNext,
            'has_prev' => $hasPrev,
            'older_qs' => $olderQs,
            'newer_qs' => $newerQs,
        ]);
    }

    #[Route(path: '/{id<\d+>}', name: 'system_api_usage_show', methods: ['GET'])]
    public function show(ApiUsageLog $log): Response
    {
        return $this->render('system_log/api_usage_show.html.twig', [
            'log' => $log,
        ]);
    }
}
