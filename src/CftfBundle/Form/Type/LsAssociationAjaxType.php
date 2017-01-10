<?php

namespace CftfBundle\Form\Type;

use CftfBundle\Entity\LsAssociation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LsAssociationAjaxType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choiceList = [];
        foreach (LsAssociation::typeChoiceList() as $choice) {
            $choiceList[$choice] = $choice;
        }

        if (!$options['ajax']) {
            $builder
                ->add('uri')
                ->add('originNodeUri')
            ;
        }

        $builder
            ->add('type', ChoiceType::class, [
                'choices' => $choiceList,
            ])
            ->add('destinationNodeUri')
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => LsAssociation::class,
            'ajax' => false,
        ));
    }
}
