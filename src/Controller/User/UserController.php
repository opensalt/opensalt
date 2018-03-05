<?php

namespace App\Controller\User;

use App\Command\Email\SendUserApprovedEmailCommand;
use App\Command\CommandDispatcherTrait;
use App\Command\User\AddUserCommand;
use App\Command\User\DeleteUserCommand;
use App\Command\User\SuspendUserCommand;
use App\Command\User\ActivateUserCommand;
use App\Command\User\UpdateUserCommand;
use App\Entity\User\User;
use App\Form\Type\UserType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * User controller.
 *
 * @Route("admin/user")
 * @Security("is_granted('manage', 'users')")
 */
class UserController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * Lists all user entities.
     *
     * @Route("/", name="admin_user_index")
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function indexAction(AuthorizationCheckerInterface $authChecker)
    {
        $em = $this->getDoctrine()->getManager();

        if ($authChecker->isGranted('ROLE_SUPER_USER')) {
            $users = $em->getRepository(User::class)->findAll();
        } else {
            $users = $em->getRepository(User::class)
                ->findByOrg($this->getUser()->getOrg());
        }
        return [
            'users' => $users,
        ];
    }

    /**
     * Creates a new user entity.
     *
     * @Route("/new", name="admin_user_new")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function newAction(Request $request, AuthorizationCheckerInterface $authChecker, PasswordEncoderInterface $passwordEncoder)
    {
        $targetUser = new User();
        $form = $this->createForm(UserType::class, $targetUser, ['validation_groups' => ['registration']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set to organization to match the creating users, unless the super-user
            if (!$authChecker->isGranted('ROLE_SUPER_USER')) {
                $targetUser->setOrg($this->getUser()->getOrg());
            }

            // Encode the plaintext password
            $encryptedPassword = $passwordEncoder
                ->encodePassword($targetUser, $targetUser->getPlainPassword());

            try {
                $command = new AddUserCommand($targetUser, $encryptedPassword);
                $this->sendCommand($command);

                return $this->redirectToRoute('admin_user_index');
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }

        }

        return [
            'user' => $targetUser,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/{id}", name="admin_user_show")
     * @Security("is_granted('manage', targetUser)")
     * @Method("GET")
     * @Template()
     *
     * @param User $targetUser
     *
     * @return array
     */
    public function showAction(User $targetUser)
    {
        $deleteForm = $this->createDeleteForm($targetUser);

        return [
            'user' => $targetUser,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/{id}/edit", name="admin_user_edit")
     * @Security("is_granted('manage', targetUser)")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param User $targetUser
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, User $targetUser, PasswordEncoderInterface $passwordEncoder)
    {
        $deleteForm = $this->createDeleteForm($targetUser);
        $editForm = $this->createForm(UserType::class, $targetUser);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $plainPassword = $targetUser->getPlainPassword();
            if (!empty($plainPassword)) {
                $password = $passwordEncoder
                    ->encodePassword($targetUser, $targetUser->getPlainPassword());
                $targetUser->setPassword($password);
            }

            try {
                $command = new UpdateUserCommand($targetUser);
                $this->sendCommand($command);

                return $this->redirectToRoute('admin_user_index');
            } catch (\Exception $e) {
                $editForm->addError(new FormError($e->getMessage()));
            }
        }

        return [
            'user' => $targetUser,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Suspend a user
     *
     * @Route("/{id}/suspend", name="admin_user_suspend")
     * @Security("is_granted('manage', targetUser)")
     * @Method({"GET"})
     *
     * @param Request $request
     * @param User $targetUser
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function suspendAction(Request $request, User $targetUser) {
        $command = new SuspendUserCommand($targetUser);
        $this->sendCommand($command);

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Activate a user
     *
     * @Route("/{id}/activate", name="admin_user_activate")
     * @Security("is_granted('manage', targetUser)")
     * @Method({"GET"})
     *
     * @param Request $request
     * @param User $targetUser
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function activateAction(Request $request, User $targetUser) {
        $command = new ActivateUserCommand($targetUser);
        $this->sendCommand($command);

        // Send email after user has been approved
        try {
            $command = new SendUserApprovedEmailCommand($targetUser->getUsername());
            $this->sendCommand($command);
        } catch (\Swift_RfcComplianceException $e) {
            throw new \RuntimeException('A valid email address must be given.');
        } catch (\Exception $e) {
            // Do not throw an error to the client if the email could not be sent
        }

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Deletes a user entity.
     *
     * @Route("/{id}", name="admin_user_delete")
     * @Security("is_granted('manage', targetUser)")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param User $targetUser
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, User $targetUser)
    {
        $form = $this->createDeleteForm($targetUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteUserCommand($targetUser);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Creates a form to delete a user entity.
     *
     * @param User $targetUser The user entity
     *
     * @return \Symfony\Component\Form\FormInterface The form
     */
    private function createDeleteForm(User $targetUser): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_user_delete', array('id' => $targetUser->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

    /**
     * Reject a user
     *
     * @Route("/{id}/reject", name="admin_user_reject")
     * @Security("is_granted('manage', targetUser)")
     * @Method({"GET"})
     *
     * @param User $targetUser
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function rejectAction(User $targetUser) {
        $command = new SuspendUserCommand($targetUser);
        $this->sendCommand($command);

        return $this->redirectToRoute('admin_user_index');
    }

}
