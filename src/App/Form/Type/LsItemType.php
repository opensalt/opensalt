<?php

namespace App\Form\Type;

use App\Entity\Framework\LsDefGrade;
use App\Entity\Framework\LsDefItemType;
use App\Form\DataTransformer\EducationAlignmentTransformer;
use App\Form\DataTransformer\ItemTypeTransformer;
use App\Repository\Framework\LsDefGradeRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

/**
 * Class UserType
 *
 * @DI\Service()
 * @DI\Tag("form.type")
 */
class LsItemType extends AbstractType
{
    /** @var ObjectManager */
    private $manager;

    /**
     * Constructor.
     *
     * @param ObjectManager $manager
     *
     * @DI\InjectParams({
     *     "manager" = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(ObjectManager $manager)
    {
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
            //->add('identifier', null, ['attr'=>['placeholder'=>'hhhhhhhh-hhhh-hhhh-hhhh-hhhhhhhhhhhh']])
            ->add('listEnumInSource')
            ->add('abbreviatedStatement')
            ->add('conceptKeywords')
            ->add('conceptKeywordsUri')
            ->add('language', LanguageType::class, [
                'required' => false,
                'preferred_choices' => ['en', 'es', 'fr'],
            ])
            ->add('educationalAlignment', EntityType::class, [
                'class' => 'App\Entity\Framework\LsDefGrade',
                'label' => 'Education Level',
                'choice_label' => 'code',
                'choice_attr' => function (LsDefGrade $val, $key, $index) {
                    return ['data-title' => $val->getTitle()];
                },
                'required' => false,
                'multiple' => true,
                'query_builder' => function (EntityRepository $er) {
                    /* @var LsDefGradeRepository $er */
                    return $er->createQueryBuilder('g')
                        ->addOrderBy('g.rank')
                        ;
                },
            ])
//            ->add('educationalAlignment', EntityType::class, [
//                'class' => 'App\Entity\Framework\LsItem',
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
                'class' => LsDefItemType::class,
                'primary_key' => 'id',
                'text_property' => 'title',
                'transformer' => ItemTypeTransformer::class,
                'minimum_input_length' => 0,
                'page_limit' => 1000,
                'scroll' => true,
                'allow_clear' => true,
                'delay' => 250,
                'placeholder' => 'Select Item Type',
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
            'data_class' => 'App\Entity\Framework\LsItem',
            'ajax' => false,
        ]);
    }
}
