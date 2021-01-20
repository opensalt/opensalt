<?php

namespace App\Form\Type;

use App\Entity\Framework\AdditionalField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class CustomFieldsType extends AbstractType
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $appliesTo = $options['applies_to'];

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($appliesTo) {
            // Check if any records exist on additional_field table where appliesTo = lsitem
            // If any exist add those fields to the form.
            $form = $event->getForm();

            $fields = $this->em->getRepository(AdditionalField::class)->findBy(['appliesTo' => $appliesTo]);

            /** @var AdditionalField $field */
            foreach ($fields as $field) {
                $typeInfo = $field->getTypeInfo();

                switch ($field->getType()) {
                    case 'string':
                        $constraints = [];
                        if (!empty($typeInfo['required'])) {
                            $constraints[] = new NotNull();
                            $constraints[] = new NotBlank();
                        }

                        $form->add($field->getName(), TextType::class, [
                            'label' => $field->getDisplayName(),
                            'required' => !empty($typeInfo['required']),
                            'constraints' => $constraints,
                        ]);
                        break;
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'applies_to' => null,
        ]);
    }
}
