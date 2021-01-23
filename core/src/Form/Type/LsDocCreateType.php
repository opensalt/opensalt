<?php

namespace App\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class LsDocCreateType extends AbstractLsDocCreateType
{
    protected function addOwnership(FormBuilderInterface $builder): void
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
