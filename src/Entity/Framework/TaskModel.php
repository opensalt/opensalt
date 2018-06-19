<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Framework\TaskModelRepository")
 */
class TaskModel
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ Column(type="text")
     */
    private $taskNarrative;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $depthOfKnowledge;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $itemType;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $availableTools;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $accessibilityConcerns;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $taskModelVariables;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $passageStimulusSpecCode;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $commonErrorsMisconceptions;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $stemRequirments;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $keyRequirments;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $distractorRequirments;

    /**
     * @ORM\ Column(type="string", length=255)
     * tei stands for tchnology enhanced interaction
     */
    private $teiGuidelines;

    /**
     * @ORM\ Column(type="text")
     */
    private $taskModelNotes;

    /**
     * @ORM\ Column(type="text")
     */
    private $exampleItems;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $rubricScoringRules;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $itemAuthoringTips;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $commonAuthoringProblems;

    public function getId()
    {
        return $this->id;
    }
}
