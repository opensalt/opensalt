<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Framework\AdditionalField;
use App\Entity\Framework\LsDefGrade;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;
use App\Entity\Framework\LsItem;
use App\Form\DataTransformer\EducationAlignmentTransformer;
use App\Util\ChoiceDeduper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * @extends AbstractType<LsItem>
 */
class LsItemType extends AbstractType
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var LsItem $item */
        $item = $builder->getData();

        if (!$options['ajax']) {
            $builder
                ->add('uri')
                ->add('lsDoc')
            ;
        }

        $builder
            ->add('fullStatement', TextareaType::class, [
                'sanitize_html' => true,
            ])
            ->add('humanCodingScheme')
            // ->add('identifier', null, ['attr'=>['placeholder'=>'hhhhhhhh-hhhh-hhhh-hhhh-hhhhhhhhhhhh']])
            ->add('listEnumInSource')
            ->add('abbreviatedStatement')
            ->add('conceptKeywords')
//            ->add('conceptKeywordsUri')
            ->add('language', LanguageType::class, [
                'required' => false,
                'preferred_choices' => ['en', 'es', 'fr'],
            ])
            ->add('educationalAlignment', EntityType::class, [
                'class' => LsDefGrade::class,
                'label' => 'Education Level',
                'choice_label' => 'code',
                'choice_attr' => static fn (LsDefGrade $val, string $key, mixed $index): array => ['data-title' => $val->getTitle()],
                'required' => false,
                'multiple' => true,
                'query_builder' => static fn (EntityRepository $er): QueryBuilder => $er->createQueryBuilder('g')->addOrderBy('g.rank'),
            ])
            ->add('itemType', EntityType::class, [
                'autocomplete' => true,
                'required' => false,
                'class' => LsDefItemType::class,
                'choice_label' => 'title',
                'multiple' => false,
                'choices' => ChoiceDeduper::onePerLabel(
                    $this->em->getRepository(LsDefItemType::class)->findAll(),
                    [$item->getItemType()],
                    static fn (LsDefItemType $itemType): ?string => $itemType->getTitle(),
                ),
                'placeholder' => 'Select Item Type',
            ])
            ->add('subjects', EntityType::class, [
                'autocomplete' => true,
                'required' => false,
                'class' => LsDefSubject::class,
                'choice_label' => 'title',
                'multiple' => true,
                'choices' => ChoiceDeduper::onePerLabel(
                    $this->em->getRepository(LsDefSubject::class)->findAll(),
                    $item->getSubjects(),
                    static fn (LsDefSubject $subject): ?string => $subject->getTitle(),
                ),
                'tom_select_options' => [
                    'placeholder' => 'Select Subjects',
                    'closeAfterSelect' => false,
                ],
            ])
            ->add('licence', EntityType::class, [
                'class' => LsDefLicence::class,
                'label' => 'License',
                'choice_label' => 'title',
                'required' => false,
                'choices' => ChoiceDeduper::onePerLabel(
                    $this->em->getRepository(LsDefLicence::class)->findAll(),
                    [$item->getLicence()],
                    static fn (LsDefLicence $licence): ?string => $licence->getTitle(),
                ),
            ])
            ->add('notes')
        ;

        $fields = $this->em->getRepository(AdditionalField::class)->findBy(['appliesTo' => LsItem::class]);
        if ([] !== $fields) {
            $builder->add('additional_fields', CustomFieldsType::class, [
                'applies_to' => LsItem::class,
                'label' => 'Additional fields',
                'constraints' => [new Valid()],
            ]);
        }

        $builder
            ->get('educationalAlignment')
            ->addModelTransformer(new EducationAlignmentTransformer($this->em))
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LsItem::class,
            'ajax' => false,
        ]);
    }
}
