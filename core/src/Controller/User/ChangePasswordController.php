<?php

namespace App\Controller\User;

use App\Command\CommandDispatcherTrait;
use App\Command\User\ChangePasswordCommand;
use App\Entity\User\User;
use App\Form\DTO\ChangePasswordDTO;
use App\Form\Type\ChangePasswordType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ChangePasswordController.
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class ChangePasswordController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(private UserPasswordHasherInterface $passwordEncoder)
    {
    }

    #[Route(path: '/user/change-password', name: 'user_change_password')]
    public function changePasswordAction(Request $request): Response
    {
        $dto = new ChangePasswordDTO();
        $form = $this->createForm(ChangePasswordType::class, $dto);

        $form->handleRequest($request);

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new \UnexpectedValueException('Invalid user.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $encryptedPassword = $this->passwordEncoder->hashPassword($user, $form->getData()->newPassword);

            $command = new ChangePasswordCommand($user, $encryptedPassword);
            $this->sendCommand($command);

            $this->addFlash('success', 'Your password has been changed.');

            return $this->redirectToRoute('salt_index');
        }

        return $this->render('user/change_password/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
