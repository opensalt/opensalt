<?php

declare(strict_types=1);

namespace App\Form\Type\ItemType;

use App\DTO\ItemType\IdentifierDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<IdentifierDto>
 */
class IdentifierType extends AbstractType
{
    public function __construct()
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('identifier', TextType::class, [
                'label' => 'Identifier',
                'help' => 'The identifier for the parent object.  It should be a unique URI.',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'help' => 'Description of the identifier.',
                'sanitize_html' => true,
            ])
        ;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'ls_item';
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IdentifierDto::class,
        ]);
    }
}
