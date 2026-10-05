<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Framework\FrameworkType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;
use App\Entity\Framework\LsDoc;
use App\Util\ChoiceDeduper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<LsDoc>
 */
abstract class AbstractLsDocCreateType extends AbstractType
{
    public function __construct(protected EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $em = $this->em;

        /** @var LsDoc $doc */
        $doc = $builder->getData();
        $exists = null !== $doc->getId();
        $adoptionStatus = $doc->getAdoptionStatus();
        $isAdopted = ($exists && LsDoc::ADOPTION_STATUS_ADOPTED === $adoptionStatus);
        $isDeprecated = ($exists && LsDoc::ADOPTION_STATUS_DEPRECATED === $adoptionStatus);
        $disableAsAdopted = $isAdopted || $isDeprecated;
        $adoptionStatusChoices = [
            'Private Draft' => LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT,
            'Draft' => LsDoc::ADOPTION_STATUS_DRAFT,
            'Adopted' => LsDoc::ADOPTION_STATUS_ADOPTED,
            'Deprecated' => LsDoc::ADOPTION_STATUS_DEPRECATED,
        ];
        if (!in_array($adoptionStatus, LsDoc::getStatuses(), true)) {
            $adoptionStatusChoices[$adoptionStatus] = $adoptionStatus;
        }

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
            // ->add('subject')
            // ->add('subjectUri')
            ->add('subjects', EntityType::class, [
                'disabled' => $disableAsAdopted,
                'autocomplete' => true,
                'required' => false,
                'class' => LsDefSubject::class,
                'choice_label' => 'title',
                'multiple' => true,
                'choices' => ChoiceDeduper::onePerLabel(
                    $this->em->getRepository(LsDefSubject::class)->findAll(),
                    $doc->getSubjects(),
                    static fn (LsDefSubject $subject): ?string => $subject->getTitle(),
                ),
                'tom_select_options' => [
                    'placeholder' => 'Select Subjects',
                    'closeAfterSelect' => false,
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
                'choices' => $adoptionStatusChoices,
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
            ->add('licence', EntityType::class, [
                'disabled' => $disableAsAdopted,
                'autocomplete' => true,
                'required' => false,
                'class' => LsDefLicence::class,
                'choice_label' => 'title',
                'multiple' => false,
                'choices' => ChoiceDeduper::onePerLabel(
                    $this->em->getRepository(LsDefLicence::class)->findAll(),
                    [$doc->getLicence()],
                    static fn (LsDefLicence $licence): ?string => $licence->getTitle(),
                ),
                'placeholder' => 'Select Licence',
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
                static fn (?FrameworkType $frameworkType): string => null !== $frameworkType ? $frameworkType->getFrameworkType() : '',
                static function (?string $frameworkType) use ($em): ?FrameworkType {
                    if (null === $frameworkType) {
                        return null;
                    }

                    $object = $em->getRepository(FrameworkType::class)->findOneBy(['frameworkType' => $frameworkType]);

                    if (null === $object) {
                        return new FrameworkType($frameworkType);
                    }

                    return $object;
                }
            ));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LsDoc::class,
            'ajax' => false,
            // 'csrf_protection' => false,
        ]);
    }

    abstract protected function addOwnership(FormBuilderInterface $builder): void;
}
