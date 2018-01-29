<?php

namespace Salt\UserBundle\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\User\ChangePasswordCommand;
use Salt\UserBundle\Form\Type\ChangePasswordType;
use Salt\UserBundle\Form\DTO\ChangePasswordDTO;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ChangePasswordController
 *
 * @Security("is_granted('ROLE_USER')")
 */
class ChangePasswordController extends Controller
{
    use CommandDispatcherTrait;

    /**
     * @Route("/user/change-password", name="user_change_password")
     * @Template()
     *
     * @param Request $request
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
            $encryptedPassword = $this->get('security.password_encoder')->encodePassword($user, $form->getData()->newPassword);

            $command = new ChangePasswordCommand($user, $encryptedPassword);
            $this->sendCommand($command);

            $this->addFlash('success', 'Your password has been changed.');

            return $this->redirectToRoute('editor_index');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
