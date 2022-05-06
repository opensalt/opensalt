<?php

namespace App\Form\DataTransformer;

use App\Entity\Framework\LsDefGrade;
use App\Repository\Framework\LsDefGradeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

class EducationAlignmentTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $manager)
    {
    }

    /**
     * @param ?string $gradeString
     */
    public function transform($gradeString): array
    {
        if (null === $gradeString) {
            return [];
        }

        /** @var LsDefGradeRepository $repo */
        $repo = $this->manager->getRepository(LsDefGrade::class);

        $grades = explode(',', $gradeString);

        return $repo->findBy(['code' => $grades]);
    }

    /**
     * @param array<array-key, LsDefGrade>|null $alignmentArray
     */
    public function reverseTransform($alignmentArray): ?string
    {
        if (null === $alignmentArray || !is_array($alignmentArray) || 0 === count($alignmentArray)) {
            return null;
        }

        $grades = array_map(static fn (LsDefGrade $alignment) => $alignment->getCode(), $alignmentArray);

        return implode(',', $grades);
    }
}
