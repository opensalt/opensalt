<?php

namespace CftfBundle\Form;

use CftfBundle\Entity\LsDefGrade;
use CftfBundle\Entity\LsItem;
use CftfBundle\Form\DataTransformer\EducationAlignmentTransformer;
use CftfBundle\Repository\LsDefGradeRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class LsItemType extends AbstractType
{
    /** @var ObjectManager */
    private $manager;

    public function __construct(ObjectManager $manager) {
        $this->manager = $manager;
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
        }

        $builder
            ->add('fullStatement')
            ->add('humanCodingScheme')
            ->add('identifier', null, ['attr'=>['placeholder'=>'hhhhhhhh-hhhh-hhhh-hhhh-hhhhhhhhhhhh']])
            ->add('listEnumInSource')
            ->add('abbreviatedStatement')
            ->add('conceptKeywords')
            ->add('conceptKeywordsUri')
            ->add('language', 'Symfony\Component\Form\Extension\Core\Type\LanguageType', [
                'required' => false,
                'preferred_choices' => ['en', 'es', 'fr'],
            ])
            ->add('educationalAlignment', EntityType::class, [
                'class' => 'CftfBundle:LsDefGrade',
                'label' => 'Education Level',
                'choice_label' => 'code',
                'choice_attr' => function ($val, $key, $index) {
                    /** @var $val LsDefGrade */
                    return ['data-title' => $val->getTitle()];
                },
                'required' => false,
                'multiple' => true,
                'query_builder' => function (EntityRepository $er) {
                    /** @var LsDefGradeRepository $er */
                    return $er->createQueryBuilder('g')
                        ->addOrderBy('g.rank')
                        ;
                }
            ])
//            ->add('educationalAlignment', EntityType::class, [
//                'class' => 'CftfBundle:LsItem',
//                'label' => 'Education Level',
//                'choice_label' => 'label',
//                'required' => false,
//                'group_by' => function($val, $key, $index) {
//                    /** @var LsItem $val */
//                    return $val->getLsDoc()->getTitle();
//                },
//                'multiple' => true,
//                //'expanded' => true,
//                'query_builder' => function (EntityRepository $er) {
//                    /** @var LsItemRepository $er */
//                    return $er->createGradeSelectListQueryBuilder();
//                }
//            ])
            //->add('type')
            ->add('itemType', Select2EntityType::class, [
                'multiple' => false,
                'remote_route' => 'lsdef_item_type_index_json',
                'class' => 'CftfBundle:LsDefItemType',
                'primary_key' => 'id',
                'text_property' => 'title',
                'minimum_input_length' => 0,
                'page_limit' => 50,
                'allow_clear' => true,
                'delay' => 250,
                'placeholder' => 'Select Item Type',
                'allow_add' => [
                    'enable' => true,
                    'new_tag_text' => '(NEW) ',
                    'new_tag_prefix' => '___',
                    'tag_separators' => ',',
                ]
            ])
            ->add('licenceUri')
            ->add('notes')

            /*
            ->add('changedAt', 'Symfony\Component\Form\Extension\Core\Type\DateTimeType', [
                'required' => false,
                //'widget' => 'single_text',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
            ])
            */
        ;
        if (!$options['ajax']) {
            $builder
                ->add('children', EntityType::class, [
                    'class' => 'CftfBundle:LsItem',
                    'required' => FALSE,
                    'multiple' => TRUE,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('i')
                            ->orderBy('i.uri', 'ASC');
                    },
                ]);
        }

        $builder->get('educationalAlignment')
            ->addModelTransformer(new EducationAlignmentTransformer($this->manager))
            ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'CftfBundle\Entity\LsItem',
            'ajax' => false,
        ]);
    }
}
