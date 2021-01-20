<?php

namespace App\Form\Type;

use App\Entity\Framework\AdditionalField;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AdditionalFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $appliesToChoices = [
            'LsItem' => LsItem::class,
            'LsAssociation' => LsAssociation::class,
            'LsDoc' => LsDoc::class,
        ];
        $typeChoices = array_combine(AdditionalField::getTypes(), AdditionalField::getTypes());

        $builder
            ->add('name', TextType::class, [
                'help' => 'Unique name of field.  Must be lower case alpha-numeric. Underscores are allowed before a letter.  The first character must be a letter.',
            ])
            ->add('displayName', TextType::class, [
                'help' => 'Text displayed as the label for this field.',
            ])
            ->add('appliesTo', ChoiceType::class, ['choices' => $appliesToChoices])
            ->add('type', ChoiceType::class, ['choices' => $typeChoices])
            ->add('typeInfo', TextareaType::class, [
                'required' => false,
                'help' => 'Additional information in JSON format specific to the type selected.',
            ])
            ->add('save', SubmitType::class)
        ;

        $builder->get('typeInfo')
            ->addModelTransformer(new CallbackTransformer(
                function (?array $infoAsArray) {
                    if (null === $infoAsArray) {
                        return null;
                    }

                    return json_encode($infoAsArray);
                },
                function (?string $infoAsString) {
                    if (null === $infoAsString) {
                        return null;
                    }

                    $json = json_decode($infoAsString, true);
                    if (JSON_ERROR_NONE !== json_last_error()) {
                        throw new TransformationFailedException('Error in JSON');
                    }

                    return $json;
                }
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdditionalField::class,
        ]);
    }
}
