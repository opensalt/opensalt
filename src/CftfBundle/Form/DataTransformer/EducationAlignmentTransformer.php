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

    public function __construct(ObjectManager $manager) {
        $this->manager = $manager;
    }

    public function transform($gradeString) {
        $alignments = new ArrayCollection();

        if (null === $gradeString) {
            return $alignments;
        }

        /** @var LsDefGradeRepository $repo */
        $repo = $this->manager->getRepository('CftfBundle:LsDefGrade');

        $grades = preg_split('/,/', $gradeString);
        $alignments = $repo->findBy(['code' => $grades]);

        if (null == $alignments) {
            $alignments = new ArrayCollection();
        }

        return $alignments;
    }

    public function reverseTransform($alignmentArray) {
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
