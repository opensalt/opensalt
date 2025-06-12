<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Framework\LsDefConcept;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<LsDefConcept>
 */
class LsDefConceptType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'required' => true,
            ])
            ->add('description')
            ->add('hierarchyCode')
            ->add('keywords')
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LsDefConcept::class,
        ]);
    }
}
