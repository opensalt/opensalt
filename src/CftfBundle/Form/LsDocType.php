<?php

namespace CftfBundle\Form;

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
        if (!$options['ajax']) {
            $builder->add('uri');
        }

        $builder
            ->add('title')
            ->add('creator')
            ->add('identifier', null, ['attr'=>['placeholder'=>'hhhhhhhh-hhhh-hhhh-hhhh-hhhhhhhhhhhh']])
            ->add('officialUri')
            ->add('publisher')
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
                'preferred_choices' => [ 'en', 'es', 'fr' ],
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
