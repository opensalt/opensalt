<?php

declare(strict_types=1);

namespace App\Form\Type\ItemType;

use App\DTO\ItemType\OrganizationDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<OrganizationDto>
 */
class OrganizationType extends AbstractType
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
                'help' => 'Name or title of the organization.',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'help' => 'Description of the organization.',
                'sanitize_html' => true,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Organization Type',
                'required' => false,
                'choices' => [
                    'Assessment Body' => 'orgType:AssessmentBody',
                    'Business' => 'orgType:Business',
                    'Business Association' => 'orgType:BusinessAssociation',
                    'Certification Body' => 'orgType:CertificationBody',
                    'Collaborative' => 'orgType:Collaborative',
                    'Coordinating Body' => 'orgType:CoordinatingBody',
                    'Four-Year College' => 'orgType:FourYear',
                    'Government Agency' => 'orgType:Government',
                    'High School' => 'orgType:HighSchool',
                    'Labor Union' => 'orgType:LaborUnion',
                    'Magnet/Competitive Admissions School' => 'orgType:Magnet',
                    'Military' => 'orgType:Military',
                    'Alternative/Non-Traditional School' => 'orgType:NonTraditional',
                    'Postsecondary Educational Institution' => 'orgType:Postsecondary',
                    'Primarily Online' => 'orgType:PrimarilyOnline',
                    'Professional Association' => 'orgType:ProfessionalAssociation',
                    'Quality Assurance Body' => 'orgType:QualityAssurance',
                    'Secondary School' => 'orgType:SecondarySchool',
                    'Career and Technical School' => 'orgType:Technical',
                    'Education and Training Provider' => 'orgType:TrainingProvider',
                    'Two-Year College' => 'orgType:TwoYear',
                    'Vendor' => 'orgType:Vendor',
                ],
                'help' => 'The type of organization.',
            ])
            ->add('logo', TextType::class, [
                'label' => 'Logo URI',
                'required' => false,
                'help' => "The organization's logo.",
            ])
            ->add('legalName', TextType::class, [
                'label' => 'Legal Name',
                'required' => false,
                'help' => "The organization's legal name.",
            ])
            ->add('ctid', TextType::class, [
                'label' => 'CTID',
                'required' => false,
                'help' => "The organization's CTID.",
            ])
            /*
            ->add('rorId', TextType::class, [
                'label' => 'ROR ID',
                'required' => false,
                'help' => 'The organization\'s ROR ID (https://ror.org).',
            ])
            */
            ->add('webpage', UrlType::class, [
                'label' => 'Webpage',
                'required' => false,
                'help' => 'Webpage that describes this organization.',
            ])
            ->add('jurisdiction', TextType::class, [
                'label' => 'Jurisdiction',
                'required' => false,
                'help' => 'Geographic or political region of the organization.',
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
            'data_class' => OrganizationDto::class,
        ]);
    }
}
