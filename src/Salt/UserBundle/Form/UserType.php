<?php

namespace Salt\UserBundle\Form;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class UserType
 *
 * @DI\Service()
 * @DI\Tag("form.type")
 */
class UserType extends AbstractType
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * UserType constructor.
     *
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authorizationChecker
     *
     * @DI\InjectParams({
     *     "authorizationChecker" = @DI\Inject("security.authorization_checker")
     * })
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $roleChoices = [];
        if ($this->authorizationChecker->isGranted('ROLE_SUPER_USER')) {
            $roleChoices = [
                'Super User' => 'ROLE_SUPER_USER',
                'Super Editor' => 'ROLE_SUPER_EDITOR',
            ];
        }
        $roleChoices['Organization Admin'] = 'ROLE_ADMIN';
        $roleChoices['Editor'] = 'ROLE_EDITOR';
        //$roleChoices['User'] = 'ROLE_USER';

        $builder
            ->add('username', TextType::class, [
                //'disabled' => !in_array('registration', $options['validation_groups']),
            ])
            ->add('plainPassword', TextType::class, [
                'required' => in_array('registration', $options['validation_groups'], true),
                'label' => 'Password',
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => $roleChoices,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
        ;

        if ($this->authorizationChecker->isGranted('ROLE_SUPER_USER')) {
            $builder->add('org', EntityType::class, [
                'class' => 'Salt\UserBundle\Entity\Organization',
                'choice_label' => 'name',
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Salt\UserBundle\Entity\User',
            'validation_groups' => ['Default'],
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'salt_userbundle_user';
    }
}
