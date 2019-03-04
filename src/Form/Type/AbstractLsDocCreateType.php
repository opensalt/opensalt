<?php

namespace App\Form\Type;

use App\Entity\Framework\LsDefFrameworkType;
use App\Entity\Framework\LsDoc;
use App\Repository\Framework\LsDocRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

abstract class AbstractLsDocCreateType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * AbstractLsDocCreateType constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->em;

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
                'class' => 'App\Entity\Framework\LsDefSubject',
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
                'class' => 'App\Entity\Framework\LsDefLicence',
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
            ->add('frameworkType', DatalistType::class, [
                'required' => false,
                'label' => 'Framework Type',
                'class' => \App\Entity\Framework\LsDefFrameworkType::class,
                'choice_label' => 'value',
                'attr'=>['autocomplete' => 'off'],
            ])
        ;

        $builder->get('frameworkType')
            ->resetViewTransformers()
            ->resetModelTransformers()
            ->addModelTransformer(new CallbackTransformer(
                function ($frameworkType) use ($em) {
                    return $frameworkType ? $frameworkType->getValue() : '';
                },
                function ($frameworkType) {
                    if ($frameworkType === null) {
                        return null;
                    }

                        $object = new LsDefFrameworkType();
                        $object->setValue($frameworkType);
                        return $object;
                    }
                }
            ));

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
            'data_class' => 'App\Entity\Framework\LsDoc',
            'ajax' => false,
            //'csrf_protection' => false,
        ));
    }

    abstract protected function addOwnership(FormBuilderInterface $builder);
}
