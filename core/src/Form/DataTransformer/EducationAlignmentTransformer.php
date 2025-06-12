<?php

declare(strict_types=1);

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
    #[\Override]
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
     * @psalm-suppress MoreSpecificImplementedParamType
     * @param array<array-key, LsDefGrade>|null $value
     */
    #[\Override]
    public function reverseTransform(mixed $value): ?string
    {
        if (!is_array($value) || [] === $value) {
            return null;
        }

        $grades = array_map(static fn (LsDefGrade $alignment): string => $alignment->getCode(), $value);

        return implode(',', $grades);
    }
}
