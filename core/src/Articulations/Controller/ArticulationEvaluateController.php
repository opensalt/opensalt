<?php

declare(strict_types=1);

namespace App\Articulations\Controller;

use App\Articulations\DTO\EvaluateRequest;
use App\Articulations\DTO\EvaluateResponse;
use App\Articulations\Service\ArticulationEvaluateService;
use App\Articulations\Service\ArticulationFrameworkResolver;
use App\Security\Permission;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Security(name: 'Bearer')]
#[OA\Response(
    response: 401,
    description: 'Missing or invalid bearer token',
)]
#[OA\Response(
    response: 403,
    description: 'Authenticated, but the token lacks FRAMEWORK_VIEW on the resolved articulation document',
)]
#[OA\Response(
    response: 404,
    description: 'No articulation CFPackage found for the sending/receiving institution pair',
)]
#[OA\Tag(
    name: 'Articulations',
    description: 'Evaluate student sending courses against articulation frameworks stored as CASE 1.1 CFPackages',
)]
final class ArticulationEvaluateController extends AbstractController
{
    public function __construct(
        private readonly ArticulationFrameworkResolver $resolver,
        private readonly ArticulationEvaluateService $evaluateService,
    ) {
    }

    #[Route(
        '/api/v1/articulations/evaluate',
        name: 'api_v1_articulations_evaluate',
        methods: ['POST'],
    )]
    #[OA\Post(
        operationId: 'api_v1_articulations_evaluate',
        summary: 'Evaluate articulations',
        description: <<<'DESC'
Evaluate a set of **sending** (transfer-from) courses against the articulation rules
between a sending institution and a receiving institution.

### How it works
1. Resolve the articulation CFPackage for the institution pair (latest academic year, then highest document id).
2. Match each request course to Course items referenced by the articulation package’s associations (not the full catalog), using typed identifiers (first match wins).
3. For each `ext:articulation` rule, build the sending requirement tree (AND/OR) and evaluate it.
4. Return only results with status **satisfied** or **partial**, each with:
   - recursive `requirement` tree (source of truth)
   - computed DNF `paths` for checklist UIs
   - `receiving` course or series

### Supported identifier types
**Institutions:** `identifier`, `uri`, plus extension keys on the linked organization CFItem (e.g. `coci:schoolId`, `codes:etsCode`, `ais:institutionId`)  
**Courses:** `identifier`, `uri`, `courseCode`, plus extension keys on the course CFItem (e.g. `coci:courseId`)

Sending courses are matched only against Course items linked via the package’s `ext:articulation` and `isPartOf` associations—not the full institution catalog.
DESC
    )]
    #[OA\RequestBody(
        description: 'Sending/receiving institutions plus the student’s completed sending courses',
        required: true,
        content: new Model(type: EvaluateRequest::class),
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Evaluation completed. `results` contains only satisfied/partial articulations (may be empty).',
        content: new Model(type: EvaluateResponse::class),
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'Request body failed validation (missing institutions, empty identifier lists, etc.)',
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function evaluate(
        #[MapRequestPayload] EvaluateRequest $request,
    ): JsonResponse {
        $sending = $request->sendingInstitution->toEntityIdentifiers();
        $receiving = $request->receivingInstitution->toEntityIdentifiers();

        $resolution = $this->resolver->resolve($sending, $receiving);
        if (null === $resolution) {
            throw $this->createNotFoundException('No articulation framework found for the given institutions.');
        }

        $this->denyAccessUnlessGranted(Permission::FRAMEWORK_VIEW, $resolution->doc);

        $payload = $this->evaluateService->evaluateDocument(
            $resolution->doc,
            $resolution->sending,
            $resolution->receiving,
            $request->mapSendingCourses(),
        );

        return $this->json($payload, Response::HTTP_OK, [], [
            'json_encode_options' => \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION,
        ]);
    }
}
