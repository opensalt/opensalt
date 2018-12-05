<?php

namespace App\Form\Type;

use App\Entity\Framework\AdditionalField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AdditionalFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $appliesToChoices = array(
            'LsItem' => 'lsitem',
            'LsAssociation' => 'lsassociation',
            'LsDoc' => 'LsDoc',
        );
        $builder
            ->add('name')
            ->add('displayName')
            ->add('appliesTo', ChoiceType::class, array('choices' => $appliesToChoices))
            ->add('type', null, ['data' => 'string'])
            ->add('typeInfo', null, ['required' => false])
            ->add('save', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AdditionalField::class,
        ]);
    }
}
