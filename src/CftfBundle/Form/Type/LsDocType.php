<?php

namespace CftfBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class LsDocType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('creator')
            ->add('officialUri')
            ->add('publisher')

            // TODO: These are placeholder, they should be determined upon creation with a choice of Org or User ownership
            ->add('org', EntityType::class, [
                'required' => false,
                'disabled' => true,
                'placeholder' => 'None',
                'label' => 'Owning Organization',
                'class' => 'Salt\UserBundle\Entity\Organization',
                'choice_label' => 'name',
            ])
            ->add('user', EntityType::class, [
                'required' => false,
                'disabled' => true,
                'placeholder' => 'None',
                'label' => 'Owning User',
                'class' => 'Salt\UserBundle\Entity\User',
                'choice_label' => 'username',
            ])

            ->add('version')
            ->add('description')
            ->add('subject')
            ->add('subjectUri')
            ->add('subjects', Select2EntityType::class, [
                'multiple' => true,
                'remote_route' => 'lsdef_subject_index_json',
                'class' => 'CftfBundle:LsDefSubject',
                'primary_key' => 'id',
                'text_property' => 'title',
                'minimum_input_length' => 0,
                'page_limit' => 50,
                'allow_clear' => true,
                'delay' => 250,
                'placeholder' => 'Select Subjects',
                'allow_add' => [
                    'enable' => false,
                    'new_tag_text' => '(NEW) ',
                    'new_tag_prefix' => '___',
                    'tag_separators' => ',',
                ]
            ])
            ->add('language', 'Symfony\Component\Form\Extension\Core\Type\LanguageType', [
                'required' => false,
                'label' => 'Language',
                'preferred_choices' => ['en', 'es', 'fr'],
            ])
            ->add('adoptionStatus', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Draft' => 'Draft',
                    'Adopted' => 'Adopted',
                    'Deprecated' => 'Deprecated',
                ]
            ])
            ->add('statusStart', 'Symfony\Component\Form\Extension\Core\Type\DateType', [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('statusEnd', 'Symfony\Component\Form\Extension\Core\Type\DateType', [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('note')
        ;

        /*
        if (!$options['ajax']) {
            $builder->add('topLsItems');
        }
        */
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CftfBundle\Entity\LsDoc',
            'ajax' => false,
            //'csrf_protection' => false,
        ));
    }
}
