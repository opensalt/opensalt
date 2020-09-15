<?php

namespace App\Form\Type;

use App\Entity\Framework\LsDoc;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class LsDocListType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;

    public function __construct(EntityManagerInterface $em, AuthorizationCheckerInterface $authChecker)
    {
        $this->em = $em;
        $this->authChecker = $authChecker;
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $repo = $this->em->getRepository(LsDoc::class);
        $list = $repo->createQueryBuilder('d')
            ->addOrderBy('d.creator', 'ASC')
            ->addOrderBy('d.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        /** @var LsDoc $doc */
        foreach ($list as $i => $doc) {
            // Optimization: All but "Private Draft" are viewable to everyone, only auth check "Private Draft"
            if (LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT === $doc->getAdoptionStatus() && !$this->authChecker->isGranted('view', $doc)) {
                unset($list[$i]);
            }
        }

        $builder
            ->add('lsDoc', EntityType::class, [
                'label' => 'Document:',
                'choice_label' => function (LsDoc $val) {
                    $title = $val->getTitle();
                    if (strlen($title) > 60) {
                        return mb_substr($val->getTitle(), 0, 59)."\u{2026}";
                    }

                    return $title;
                },
                'group_by' => function (LsDoc $val) {
                    $creator = $val->getCreator();
                    if (strlen($creator) > 60) {
                        return mb_substr($val->getCreator(), 0, 59)."\u{2026}";
                    }

                    return $creator;
                },
                'required' => false,
                'multiple' => false,
                'class' => LsDoc::class,
                'choices' => $list,
            ])
        ;
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'ajax' => false,
        ]);
    }
}
