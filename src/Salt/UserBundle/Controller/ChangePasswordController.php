<?php

namespace Salt\UserBundle\Controller;

use Salt\UserBundle\Form\ChangePasswordType;
use Salt\UserBundle\Form\DTO\ChangePasswordDTO;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class ChangePasswordController extends Controller
{
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
            $newPassword = $this->get('security.password_encoder')->encodePassword($this->getUser(), $form->getData()->newPassword);

            $user = $this->getUser();
            $user->setPassword($newPassword);

            $manager = $this->get('doctrine.orm.entity_manager');
            $manager->persist($user);
            $manager->flush($user);

            $this->addFlash('success', 'Your password has been changed.');
            return $this->redirectToRoute('editor_index');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
