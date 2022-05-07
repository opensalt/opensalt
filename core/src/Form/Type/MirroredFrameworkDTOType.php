<?php

namespace App\Form\Type;

use App\Entity\Framework\Mirror\OAuthCredential;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MirroredFrameworkDTOType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url', TextType::class, [
                'label' => 'URL of framework to mirror',
                'attr' => [
                    'placeholder' => 'https://server.name.com/ims/case/v1p0/CFPackages/00000000-0000-0000-0000-000000000000',
                ],
                'help' => 'Enter the URL of the framework you want to mirror.',
            ])
            ->add('credentials', EntityType::class, [
                'label' => 'Use credentials (if required)',
                'help' => 'Select the credentials to use when authenticating with the server if they are needed.',
                'help_html' => true,
                'class' => OAuthCredential::class,
                'choice_label' => static fn (OAuthCredential $credential) => $credential->getKey().' @ '.$credential->getAuthenticationEndpoint(),
                'required' => false,
                'multiple' => false,
                'query_builder' => static fn (EntityRepository $repo) => $repo->createQueryBuilder('c')
                    ->orderBy('c.key', 'ASC')
                    ->addOrderBy('c.authenticationEndpoint', 'ASC'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
