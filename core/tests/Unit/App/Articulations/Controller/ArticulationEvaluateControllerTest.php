<?php

declare(strict_types=1);

namespace Tests\Unit\App\Articulations\Controller;

use App\Articulations\Controller\ArticulationEvaluateController;
use App\Articulations\DTO\EntityRefDto;
use App\Articulations\DTO\EvaluateRequest;
use App\Articulations\DTO\IdentifierDto;
use App\Articulations\Service\ArticulationEvaluateService;
use App\Articulations\Service\ArticulationFrameworkResolver;
use App\Articulations\Service\CourseCodeNormalizer;
use App\Articulations\Service\EntityIdentifierIndex;
use App\Articulations\Service\EntityIdentifierResolver;
use App\Articulations\Service\IdentifierMatcher;
use App\Articulations\Service\RequirementEvaluator;
use App\Articulations\Service\RequirementTreeBuilder;
use App\Articulations\Service\SendingCourseMatcher;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsDocRepository;
use App\Repository\Framework\LsItemRepository;
use App\Service\Api1Uris;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ArticulationEvaluateControllerTest extends TestCase
{
    public function testEvaluateReturns404WhenResolverReturnsNull(): void
    {
        $itemRepository = $this->createMock(LsItemRepository::class);
        $itemRepository->method('findInstitutionMatchByIdentifiers')->willReturn(null);

        $index = new EntityIdentifierIndex();
        $matcher = new IdentifierMatcher(new CourseCodeNormalizer());

        $identifierResolver = new EntityIdentifierResolver(
            $itemRepository,
            $index,
            $matcher,
        );

        $resolver = new ArticulationFrameworkResolver(
            $identifierResolver,
            $this->createMock(LsAssociationRepository::class),
            $this->createMock(LsDocRepository::class),
        );

        $evaluateService = new ArticulationEvaluateService(
            new RequirementTreeBuilder(),
            new RequirementEvaluator(),
            $itemRepository,
            $this->createMock(LsAssociationRepository::class),
            new SendingCourseMatcher($index, $matcher),
            $this->createMock(Api1Uris::class),
        );

        $sending = new EntityRefDto();
        $sending->identifiers = [new IdentifierDto('coci:schoolId', '46')];

        $receiving = new EntityRefDto();
        $receiving->identifiers = [new IdentifierDto('codes:etsCode', '4687')];

        $request = new EvaluateRequest();
        $request->sendingInstitution = $sending;
        $request->receivingInstitution = $receiving;
        $request->sendingCourses = [];

        $controller = new ArticulationEvaluateController($resolver, $evaluateService);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No articulation framework found for the given institutions.');

        $controller->evaluate($request);
    }
}
