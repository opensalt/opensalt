<?php

namespace CftfBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

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
