<?php

namespace App\Form\DataTransformer;

use App\Entity\Framework\LsDefGrade;
use App\Repository\Framework\LsDefGradeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

/** @implements DataTransformerInterface<string|null, array> */
readonly class EducationAlignmentTransformer implements DataTransformerInterface
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    /**
     * @param ?string $value
     */
    public function transform(mixed $value): array
    {
        if (null === $value) {
            return [];
        }

        /** @var LsDefGradeRepository $repo */
        $repo = $this->manager->getRepository(LsDefGrade::class);

        $grades = explode(',', $value);

        return $repo->findBy(['code' => $grades]);
    }

    /**
     * @param array<array-key, LsDefGrade>|null $value
     */
    public function reverseTransform(mixed $value): ?string
    {
        if (!is_array($value) || 0 === count($value)) {
            return null;
        }

        $grades = array_map(static fn (LsDefGrade $alignment) => $alignment->getCode(), $value);

        return implode(',', $grades);
    }
}
