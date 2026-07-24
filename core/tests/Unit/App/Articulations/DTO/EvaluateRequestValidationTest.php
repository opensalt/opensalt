<?php

declare(strict_types=1);

namespace Tests\Unit\App\Articulations\DTO;

use App\Articulations\DTO\EntityRefDto;
use App\Articulations\DTO\EvaluateRequest;
use App\Articulations\DTO\IdentifierDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class EvaluateRequestValidationTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testSendingCoursesCountIsCappedAtFifty(): void
    {
        $request = $this->validRequest();
        $request->sendingCourses = array_fill(0, 51, $this->courseRef());

        $violations = $this->validator->validate($request);

        $this->assertCount(1, $violations);
        $this->assertSame('sendingCourses', $violations->get(0)->getPropertyPath());
    }

    public function testIdentifierCountIsCappedAtTen(): void
    {
        $request = $this->validRequest();
        $request->sendingInstitution->identifiers = array_map(
            static fn (int $i): IdentifierDto => new IdentifierDto('type'.$i, 'value'.$i),
            range(1, 11),
        );

        $violations = $this->validator->validate($request);

        $this->assertCount(1, $violations);
        $this->assertSame('sendingInstitution.identifiers', $violations->get(0)->getPropertyPath());
    }

    public function testIdentifierValueLengthIsCapped(): void
    {
        $request = $this->validRequest();
        $request->sendingInstitution->identifiers = [
            new IdentifierDto('coci:schoolId', str_repeat('x', 257)),
        ];

        $violations = $this->validator->validate($request);

        $this->assertCount(1, $violations);
        $this->assertSame('sendingInstitution.identifiers[0].value', $violations->get(0)->getPropertyPath());
    }

    private function validRequest(): EvaluateRequest
    {
        $sending = new EntityRefDto();
        $sending->identifiers = [new IdentifierDto('coci:schoolId', '46')];

        $receiving = new EntityRefDto();
        $receiving->identifiers = [new IdentifierDto('identifier', 'recv-org')];

        $request = new EvaluateRequest();
        $request->sendingInstitution = $sending;
        $request->receivingInstitution = $receiving;
        $request->sendingCourses = [$this->courseRef()];

        return $request;
    }

    private function courseRef(): EntityRefDto
    {
        $course = new EntityRefDto();
        $course->identifiers = [new IdentifierDto('courseCode', 'MATH 10')];

        return $course;
    }
}
