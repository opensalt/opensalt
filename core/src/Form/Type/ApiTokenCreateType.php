<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Form\DTO\ApiTokenCreateDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApiTokenCreateType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $today = new \DateTimeImmutable('today');
        $max = $today->modify('+1 year');

        $builder
            ->add('name', TextType::class, [
                'label' => 'Token name',
                'required' => true,
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'e.g. CI server, local script',
                ],
            ])
            ->add('expiresAt', DateType::class, [
                'label' => 'Expiry date (optional)',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                // Leave default 'input' (datetime) to map to \DateTimeInterface on the DTO
                'attr' => [
                    'min' => $today->format('Y-m-d'),
                    'max' => $max->format('Y-m-d'),
                ],
                'help' => 'Pick a date up to one year in the future.',
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApiTokenCreateDTO::class,
        ]);
    }
}
