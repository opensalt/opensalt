<?php

namespace CftfBundle\Form\Type;

use CftfBundle\Entity\LsDoc;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class LsDocCreateType extends AbstractLsDocCreateType
{
    /**
     * @param FormBuilderInterface $builder
     */
    protected function addOwnership(FormBuilderInterface $builder)
    {
        $builder
            ->add('ownedBy', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'My Organization' => 'organization',
                    'Me' => 'user',
                ],
            ])
        ;
    }
}
