<?php

namespace App\Form\Type\ItemType;

use App\DTO\ItemType\PublicKeyDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<PublicKeyDto>
 */
class PublicKeyType extends AbstractType
{
    public function __construct()
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('publicKey', TextareaType::class, [
                'label' => 'Public Key',
                'help' => 'Paste the public key here as a JWK or certificate.',
            ])
            /*
            ->add('keyType', TextType::class, [
                'label' => 'Key Type',
                'help' => 'The type of the public key.',
            ])
            */
        ;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'ls_item';
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PublicKeyDto::class,
        ]);
    }
}
