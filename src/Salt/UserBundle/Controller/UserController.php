<?php

namespace Salt\UserBundle\Controller;

use Salt\UserBundle\Entity\User;
use Salt\UserBundle\Form\UserType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * User controller.
 *
 * @Route("admin/user")
 * @Security("is_granted('manage', 'users')")
 */
class UserController extends Controller
{
    /**
     * Lists all user entities.
     *
     * @Route("/", name="admin_user_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_USER')) {
            $users = $em->getRepository('SaltUserBundle:User')->findAll();
        } else {
            $admin = $this->getUser();
            $users = $em->getRepository('SaltUserBundle:User')->findByOrg($admin->getOrg());
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
     */
    public function newAction(Request $request)
    {
        $targetUser = new User();
        $form = $this->createForm(UserType::class, $targetUser, ['validation_groups' => ['registration']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set to organization to match the creating users, unless the super-user
            if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_USER')) {
                $targetUser->setOrg($this->getUser()->getOrg());
            }

            // Encode the plaintext password
            $password = $this->get('security.password_encoder')
                ->encodePassword($targetUser, $targetUser->getPlainPassword());
            $targetUser->setPassword($password);

            $em = $this->getDoctrine()->getManager();
            $em->persist($targetUser);
            $em->flush($targetUser);

            return $this->redirectToRoute('admin_user_show', array('id' => $targetUser->getId()));
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
     */
    public function editAction(Request $request, User $targetUser)
    {
        $deleteForm = $this->createDeleteForm($targetUser);
        $editForm = $this->createForm(UserType::class, $targetUser);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $plainPassword = $targetUser->getPlainPassword();
            if (!empty($plainPassword)) {
                $password = $this->get('security.password_encoder')
                    ->encodePassword($targetUser, $targetUser->getPlainPassword());
                $targetUser->setPassword($password);
            }

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('admin_user_edit', array('id' => $targetUser->getId()));
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
     */
    public function suspendAction(Request $request, User $targetUser) {
        $targetUser->suspendUser();
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Unsuspend a user
     *
     * @Route("/{id}/unsuspend", name="admin_user_unsuspend")
     * @Security("is_granted('manage', targetUser)")
     * @Method({"GET"})
     */
    public function unsuspendAction(Request $request, User $targetUser) {
        $targetUser->unsuspendUser();
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Deletes a user entity.
     *
     * @Route("/{id}", name="admin_user_delete")
     * @Security("is_granted('manage', targetUser)")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, User $targetUser)
    {
        $form = $this->createDeleteForm($targetUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($targetUser);
            $em->flush($targetUser);
        }

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Creates a form to delete a user entity.
     *
     * @param User $targetUser The user entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $targetUser)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_user_delete', array('id' => $targetUser->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
