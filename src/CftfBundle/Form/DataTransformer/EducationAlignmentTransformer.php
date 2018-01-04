<?php

namespace CftfBundle\Form\DataTransformer;

use CftfBundle\Entity\LsDefGrade;
use CftfBundle\Repository\LsDefGradeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;

class EducationAlignmentTransformer implements DataTransformerInterface
{
    private $manager;

    /**
     * EducationAlignmentTransformer constructor.
     *
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param string $gradeString
     *
     * @return array|ArrayCollection
     */
    public function transform($gradeString)
    {
        if (null === $gradeString) {
            return new ArrayCollection();
        }

        /** @var LsDefGradeRepository $repo */
        $repo = $this->manager->getRepository(LsDefGrade::class);

        $grades = preg_split('/,/', $gradeString);
        $alignments = $repo->findBy(['code' => $grades]);

        if (null === $alignments) {
            $alignments = new ArrayCollection();
        }

        return $alignments;
    }

    /**
     * @param array|Collection $alignmentArray
     *
     * @return null|string
     */
    public function reverseTransform($alignmentArray)
    {
        if (is_array($alignmentArray)) {
            $alignmentArray = new ArrayCollection($alignmentArray);
        }

        /** @var Collection $alignmentArray */
        if ($alignmentArray->isEmpty()) {
            return null;
        }

        $grades = $alignmentArray->map(function (LsDefGrade $alignment) {
            return $alignment->getCode();
        });

        return implode(',', $grades->toArray());
    }
}
