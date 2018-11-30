<?php

namespace App\Form\Type;

use App\Entity\Framework\AdditionalField;
use App\Entity\Framework\LsDoc;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class LsItemAdditionalFieldType extends AbstractType
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$options['ajax']) {
            $builder
                ->add('uri')
                ->add('lsDoc')
            ;

            $builder->get('lsDoc')->addModelTransformer(new CallbackTransformer(
                function (LsDoc $model) {
                    return $model->getUri();
                },
                function (string $uri) {
                    $doc = $this->entityManager->getRepository(LsDoc::class)->findOneBy(['uri' => $uri]);
                    return $doc;
                }
            ));
        }

        $builder
            ->add('fullStatement')
            ->add('humanCodingScheme')
            ->add('listEnumInSource')
            ->add('abbreviatedStatement')
            ->add('conceptKeywords')
            ->add('conceptKeywordsUri')
            // ->add('language', LanguageType::class, [
            //     'required' => false,
            //     'preferred_choices' => ['en', 'es', 'fr'],
            // ])
            // ->add('educationalAlignment', EntityType::class, [
            //     'class' => 'App\Entity\Framework\LsDefGrade',
            //     'label' => 'Education Level',
            //     'choice_label' => 'code',
            //     'choice_attr' => function (LsDefGrade $val, $key, $index) {
            //         return ['data-title' => $val->getTitle()];
            //     },
            //     'required' => false,
            //     'multiple' => true,
            //     'query_builder' => function (EntityRepository $er) {
            //         /* @var LsDefGradeRepository $er */
            //         return $er->createQueryBuilder('g')
            //             ->addOrderBy('g.rank')
            //             ;
            //     },
            // ])
            // ->add('itemType', Select2EntityType::class, [
            //     'multiple' => false,
            //     'remote_route' => 'lsdef_item_type_index_json',
            //     'class' => LsDefItemType::class,
            //     'primary_key' => 'id',
            //     'text_property' => 'title',
            //     'transformer' => ItemTypeTransformer::class,
            //     'minimum_input_length' => 0,
            //     'page_limit' => 1000,
            //     'scroll' => true,
            //     'allow_clear' => true,
            //     'delay' => 250,
            //     'placeholder' => 'Select Item Type',
            // ])
            ->add('licenceUri')
            ->add('notes')
        ;

        // $builder->get('educationalAlignment')
        //     ->addModelTransformer(new EducationAlignmentTransformer($this->em))
        //     ;


        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            // 1. Check if any records exist on additional_field table where appliesTo = lsItem
            // 2. If any exist add those fields to the form.

            $data = $event->getData();
            $form = $event->getForm();

            $fields = $this->entityManager->getRepository(AdditionalField::class)->findBy(['appliesTo' => 'lsItem']);

            /** @var AdditionalField $field */
            foreach ($fields as $field) {
                $form->add($field->getName(), TextType::class, [
                    'label' => $field->getDisplayName(),
                    'required' => false,
                ]);

                $data->{$field->getName()} = null;
            }

            $event->setData($data);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            // 1. Check if any records exist on additional_field table where appliesTo = lsItem
            // 2. If any exist add those fields to the form.
            $data = $event->getData();
            $form = $event->getForm();


        });
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'ajax' => false,
        ]);
    }
}
