<?php

namespace App\Form\Type;

use App\Entity\Framework\LsAssociation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LsAssociationTreeType extends AbstractType
{
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
                'label' => 'Relationship Type',
            ])
        ;
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => LsAssociation::class,
            'ajax' => false,
            //'csrf_protection' => false,
        ));
    }
}
