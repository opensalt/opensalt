<?php

namespace App\Form\Type;

use App\Entity\Framework\TaskModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class TaskModelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('taskNarrative')
            ->add('depthOfKnowledge')
            ->add('itemType')
            ->add('availableTools')
            ->add('accessibilityConcerns')
            ->add('taskModelVariables')
            ->add('passageStimulusSpecCode')
            ->add('commonErrorsMisconceptions')
            ->add('stemRequirements')
            ->add('keyRequirements')
            ->add('distractorRequirements')
            ->add('teiGuidelines')
            ->add('taskModelNotes')
            ->add('exampleItems')
            ->add('rubricScoringRules')
            ->add('itemAuthoringTips')
            ->add('commonAuthoringProblemsRequirements')
            ->add('save', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TaskModel::class,
        ]);
    }
}
