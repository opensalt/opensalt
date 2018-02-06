<?php

namespace CftfBundle\Form\Type;

use CftfBundle\Entity\LsDoc;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

abstract class AbstractLsDocCreateType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var LsDoc $doc */
        $doc = $builder->getData();
        $exists = $doc->getId() ? true : false;
        $isAdopted = ($exists && LsDoc::ADOPTION_STATUS_ADOPTED === $doc->getAdoptionStatus());
        $isDeprecated = ($exists && LsDoc::ADOPTION_STATUS_DEPRECATED === $doc->getAdoptionStatus());
        $disableAsAdopted = $isAdopted || $isDeprecated;

        $builder
            ->add('title', null, [
                'disabled' => $disableAsAdopted,
            ])
            ->add('creator', null, [
                'disabled' => $disableAsAdopted,
            ])
            ->add('officialUri', null, [
                'label' => 'Official URI',
                'disabled' => $disableAsAdopted,
            ])
            ->add('publisher', null, [
                'disabled' => $disableAsAdopted,
            ])
            ->add('urlName', null, [
                'label' => 'URL Name',
                'disabled' => $disableAsAdopted,
            ])
            ;

        $this->addOwnership($builder);

        $builder
            ->add('version', null, [
                'disabled' => $disableAsAdopted,
            ])
            ->add('description', null, [
                'disabled' => $disableAsAdopted,
            ])
            //->add('subject')
            //->add('subjectUri')
            ->add('subjects', Select2EntityType::class, [
                'disabled' => $disableAsAdopted,
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
                ],
            ])
            ->add('language', 'Symfony\Component\Form\Extension\Core\Type\LanguageType', [
                'disabled' => $disableAsAdopted,
                'required' => false,
                'label' => 'Language',
                'preferred_choices' => ['en', 'es', 'fr'],
            ])
            ->add('adoptionStatus', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Private Draft' => LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT,
                    'Draft' => LsDoc::ADOPTION_STATUS_DRAFT,
                    'Adopted' => LsDoc::ADOPTION_STATUS_ADOPTED,
                    'Deprecated' => LsDoc::ADOPTION_STATUS_DEPRECATED,
                ],
            ])
            ->add('statusStart', 'Symfony\Component\Form\Extension\Core\Type\DateType', [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('statusEnd', 'Symfony\Component\Form\Extension\Core\Type\DateType', [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('note', null, [
            ])
            ->add('licence', Select2EntityType::class, [
                'disabled' => $disableAsAdopted,
                'multiple' => false,
                'remote_route' => 'lsdef_licence_index_json',
                'class' => 'CftfBundle:LsDefLicence',
                'primary_key' => 'id',
                'text_property' => 'title',
                'minimum_input_length' => 0,
                'page_limit' => 50,
                'allow_clear' => true,
                'delay' => 250,
                'placeholder' => 'Select Licence',
                'allow_add' => [
                    'enable' => false,
                    'new_tag_text' => '(NEW) ',
                    'new_tag_prefix' => '___',
                    'tag_separators' => ',',
                ],
            ])
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

    abstract protected function addOwnership(FormBuilderInterface $builder);
}
