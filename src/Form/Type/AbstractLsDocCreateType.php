<?php

namespace App\Form\Type;

use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;
use App\Entity\Framework\FrameworkType;
use App\Entity\Framework\LsDoc;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

abstract class AbstractLsDocCreateType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * AbstractLsDocCreateType constructor.
     *
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
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                'class' => LsDefSubject::class,
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
            ->add('language', LanguageType::class, [
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
            ->add('statusStart', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('statusEnd', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('note', null, [
            ])
            ->add('licence', Select2EntityType::class, [
                'disabled' => $disableAsAdopted,
                'multiple' => false,
                'remote_route' => 'lsdef_licence_index_json',
                'class' => LsDefLicence::class,
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
                'class' => FrameworkType::class,
                'choice_label' => 'frameworkType',
                'attr' => ['autocomplete' => 'off'],
            ])
        ;

        $builder->get('frameworkType')
            ->resetViewTransformers()
            ->resetModelTransformers()
            ->addModelTransformer(new CallbackTransformer(
                static function (?FrameworkType $frameworkType): ?string {
                    return $frameworkType ? $frameworkType->getFrameworkType() : '';
                },
                static function (?string $frameworkType) use ($em): ?FrameworkType {
                    if (null === $frameworkType) {
                        return null;
                    }

                    $object = $em->getRepository(FrameworkType::class)->findOneBy(['frameworkType' => $frameworkType]);

                    if (null === $object) {
                        $object = new FrameworkType();
                        $object->setFrameworkType($frameworkType);
                    }

                    return $object;
                }
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LsDoc::class,
            'ajax' => false,
            //'csrf_protection' => false,
        ]);
    }

    abstract protected function addOwnership(FormBuilderInterface $builder): void;
}
