<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Form\DTO\MirroredFrameworkEditDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<MirroredFrameworkEditDTO>
 */
class MirroredFrameworkEditDTOType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url', TextType::class, [
                'label' => 'URL of framework to mirror',
                'attr' => [
                    'placeholder' => 'https://server.name.com/ims/case/v1p1/CFPackages/00000000-0000-0000-0000-000000000000',
                ],
                'help' => 'Enter the URL of the framework you want to mirror.',
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
