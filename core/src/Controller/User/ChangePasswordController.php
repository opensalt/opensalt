<?php

namespace App\Controller\User;

use App\Command\CommandDispatcherTrait;
use App\Command\User\ChangePasswordCommand;
use App\Form\Type\ChangePasswordType;
use App\Form\DTO\ChangePasswordDTO;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class ChangePasswordController
 *
 * @Security("is_granted('ROLE_USER')")
 */
class ChangePasswordController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Route("/user/change-password", name="user_change_password")
     * @Template()
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function changePasswordAction(Request $request)
    {
        $dto = new ChangePasswordDTO();
        $form = $this->createForm(ChangePasswordType::class, $dto);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $encryptedPassword = $this->passwordEncoder->encodePassword($user, $form->getData()->newPassword);

            $command = new ChangePasswordCommand($user, $encryptedPassword);
            $this->sendCommand($command);

            $this->addFlash('success', 'Your password has been changed.');

            return $this->redirectToRoute('salt_index');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
