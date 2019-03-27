<?php

namespace App\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;

class LsDocType extends AbstractLsDocCreateType
{
    /**
     * @param FormBuilderInterface $builder
     */
    protected function addOwnership(FormBuilderInterface $builder): void
    {
        $builder
            // TODO: These are placeholder, they should be determined upon creation with a choice of Org or User ownership
            ->add('org', EntityType::class, [
                'required' => false,
                'disabled' => true,
                'placeholder' => 'None',
                'label' => 'Owning Organization',
                'class' => 'App\Entity\User\Organization',
                'choice_label' => 'name',
            ])
            ->add('user', EntityType::class, [
                'required' => false,
                'disabled' => true,
                'placeholder' => 'None',
                'label' => 'Owning User',
                'class' => 'App\Entity\User\User',
                'choice_label' => 'username',
            ])
        ;
    }
}
