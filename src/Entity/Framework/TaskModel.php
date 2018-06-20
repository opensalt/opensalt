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
    private $stemRequirements;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $keyRequirements;

    /**
     * @ORM\ Column(type="string", length=255)
     */
    private $distractorRequirements;

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
    private $commonAuthoringProblemsRequirements;

    public function getId()
    {
        return $this->id;
    }

    /**
     * Get taskNarrative
     *
     * @return string
     */
    public function getTaskNarrative()
    {
      return $this->taskNarrative;
    }

    /**
     * Set taskNarrative
     *
     * @param string
     */
    public function setTaskNarrative($taskNarrative): void
    {
      $this->taskNarrative = $taskNarrative;
    }

    /**
     * Get depthOfKnowledge
     *
     * @return string
     */
    public function getDepthOfKnowledge()
    {
      return $this->depthOfKnowledge;
    }

    /**
     * Set depthOfKnowledge
     *
     * @param string
     */
    public function setDepthOfKnowledge($depthOfKnowledge): void
    {
      $this->depthOfKnowledge = $depthOfKnowledge;
    }

    /**
     * Get itemType
     *
     * @return string
     */
    public function getItemType()
    {
      return $this->itemType;
    }

    /**
     * Set itemType
     *
     * @param string
     */
    public function setItemType($itemType): void
    {
      $this->itemType = $itemType;
    }

    /**
     * Get availableTools
     *
     * @return string
     */
    public function getAvailableTools()
    {
      return $this->availableTools;
    }

    /**
     * Set availableTools
     *
     * @param string
     */
    public function setAvailableTools($availableTools): void
    {
      $this->availableTools = $availableTools;
    }

    /**
     * Get accessibilityConcerns
     *
     * @return string
     */
    public function getAccessibilityConcerns()
    {
      return $this->accessibilityConcerns;
    }

    /**
     * Set accessibilityConcerns
     *
     * @param string
     */
    public function setAccessibilityConcerns($accessibilityConcerns): void
    {
      $this->accessibilityConcerns = $accessibilityConcerns;
    }

    /**
     * Get taskModelVariables
     *
     * @return string
     */
    public function getTaskModelVariables()
    {
      return $this->taskModelVariables;
    }

    /**
     * Set taskModelVariables
     *
     * @param string
     */
    public function setTaskModelVariables($taskModelVariables): void
    {
      $this->taskModelVariables = $taskModelVariables;
    }

    /**
     * Get passageStimulusSpecCode
     *
     * @return string
     */
    public function getPassageStimulusSpecCode()
    {
      return $this->passageStimulusSpecCode;
    }

    /**
     * Set passageStimulusSpecCode
     *
     * @param string
     */
    public function setPassageStimulusSpecCode($passageStimulusSpecCode): void
    {
      $this->passageStimulusSpecCode = $passageStimulusSpecCode;
    }

    /**
     * Get commonErrorsMisconceptions
     *
     * @return string
     */
    public function getCommonErrorsMisconceptions()
    {
      return $this->commonErrorsMisconceptions;
    }

    /**
     * Set commonErrorsMisconceptions
     *
     * @param string
     */
    public function setCommonErrorsMisconceptions($commonErrorsMisconceptions): void
    {
      $this->commonErrorsMisconceptions = $commonErrorsMisconceptions;
    }

    /**
     * Get stemRequirements
     *
     * @return string
     */
    public function getStemRequirements()
    {
      return $this->stemRequirements;
    }

    /**
     * Set stemRequirements
     *
     * @param string
     */
    public function setStemRequirements($stemRequirements): void
    {
      $this->stemRequirements = $stemRequirements;
    }

    /**
     * Get keyRequirements
     *
     * @return string
     */
    public function getKeyRequirements()
    {
      return $this->keyRequirements;
    }

    /**
     * Set keyRequirements
     *
     * @param string
     */
    public function setKeyRequirements($keyRequirements): void
    {
      $this->keyRequirements = $keyRequirements;
    }

    /**
     * Get distractorRequirements
     *
     * @return string
     */
    public function getDistractorRequirements()
    {
      return $this->distractorRequirements;
    }

    /**
     * Set distractorRequirements
     *
     * @param string
     */
    public function setDistractorRequirements($distractorRequirements): void
    {
      $this->distractorRequirements = $distractorRequirements;
    }

    /**
     * Get teiGuidelines
     *
     * @return string
     */
    public function getTeiGuidelines()
    {
      return $this->teiGuidelines;
    }

    /**
     * Set teiGuidelines
     *
     * @param string
     */
    public function setTeiGuidelines($teiGuidelines): void
    {
      $this->teiGuidelines = $teiGuidelines;
    }

    /**
     * Get taskModelNotes
     *
     * @return string
     */
    public function getTaskModelNotes()
    {
      return $this->taskModelNotes;
    }

    /**
     * Set taskModelNotes
     *
     * @param string
     */
    public function setTaskModelNotes($taskModelNotes): void
    {
      $this->taskModelNotes = $taskModelNotes;
    }

    /**
     * Get exampleItems
     *
     * @return string
     */
    public function getExampleItems()
    {
      return $this->exampleItems;
    }

    /**
     * Set exampleItems
     *
     * @param string
     */
    public function setExampleItems($exampleItems): void
    {
      $this->exampleItems = $exampleItems;
    }

    /**
     * Get rubricScoringRules
     *
     * @return string
     */
    public function getRubricScoringRules()
    {
      return $this->rubricScoringRules;
    }

    /**
     * Set rubricScoringRules
     *
     * @param string
     */
    public function setRubricScoringRules($rubricScoringRules): void
    {
      $this->rubricScoringRules = $rubricScoringRules;
    }

    /**
     * Get itemAuthoringTips
     *
     * @return string
     */
    public function getItemAuthoringTips()
    {
      return $this->itemAuthoringTips;
    }

    /**
     * Set itemAuthoringTips
     *
     * @param string
     */
    public function setItemAuthoringTips($itemAuthoringTips): void
    {
      $this->itemAuthoringTips = $itemAuthoringTips;
    }
    
    /**
     * Get commonAuthoringProblemsRequirements
     *
     * @return string
     */
    public function getCommonAuthoringProblemsRequirements()
    {
      return $this->commonAuthoringProblemsRequirements;
    }

    /**
     * Set commonAuthoringProblemsRequirements
     *
     * @param string
     */
    public function setCommonAuthoringProblemsRequirements($commonAuthoringProblemsRequirements): void
    {
      $this->commonAuthoringProblemsRequirements = $commonAuthoringProblemsRequirements;
    }
}
