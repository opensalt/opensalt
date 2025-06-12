<?php

declare(strict_types=1);

namespace App\Form\Type\ItemType;

use App\DTO\ItemType\AssessmentDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<AssessmentDto>
 */
class AssessmentType extends AbstractType
{
    public function __construct()
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'sanitize_html' => true,
            ])
            ->add('deliveryType', ChoiceType::class, [
                'label' => 'Delivery Type',
                'required' => false,
                'choices' => [
                    'In Person' => 'in-person',
                    'Online' => 'online',
                    'Hybrid' => 'hybrid',
                ],
                'help' => 'The method of delivering this course',
            ])
            ->add('inLanguage', LanguageType::class, [
                'required' => false,
                'preferred_choices' => ['en', 'es', 'fr'],
                'help' => 'Language used to teach this course',
            ])
            ->add('keywords', TextType::class, [
                'label' => 'Keywords',
                'required' => false,
                'help' => 'Separate keywords with a comma (,)',
            ])
            ->add('webpage', UrlType::class, [
                'label' => 'Webpage',
                'required' => false,
                'help' => 'Webpage that describes this job',
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
            'data_class' => AssessmentDto::class,
        ]);
    }
}
