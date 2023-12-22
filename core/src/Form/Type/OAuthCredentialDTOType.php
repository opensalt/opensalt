<?php

namespace App\Form\Type;

use App\Form\DTO\OAuthCredentialDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<OAuthCredentialDTO>
 */
class OAuthCredentialDTOType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('authenticationEndpoint')
            ->add('key')
            ->add('secret')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OAuthCredentialDTO::class,
        ]);
    }
}
