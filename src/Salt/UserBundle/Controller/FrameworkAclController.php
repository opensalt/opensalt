<?php

namespace Salt\UserBundle\Controller;

use CftfBundle\Entity\LsDoc;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Salt\UserBundle\Entity\User;
use Salt\UserBundle\Entity\UserDocAcl;
use Salt\UserBundle\Form\Type\AddAclUsernameType;
use Salt\UserBundle\Form\Type\AddAclUserType;
use Salt\UserBundle\Form\Command\AddAclUserCommand;
use Salt\UserBundle\Form\Command\AddAclUsernameCommand;
use Salt\UserBundle\Form\DTO\AddAclUserDTO;
use Salt\UserBundle\Form\DTO\AddAclUsernameDTO;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FrameworkAclController
 *
 * @Route("/lsdoc")
 */
class FrameworkAclController extends Controller
{
    /**
     * @Route("/{id}/acl", name="framework_acl_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @Security("is_granted('manage_editors', lsDoc)")
     *
     * @param Request $request
     * @param LsDoc $lsDoc
     *
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, LsDoc $lsDoc)
    {
        $addAclUserDto = new AddAclUserDTO();
        $addOrgUserForm = $this->createForm(AddAclUserType::class, $addAclUserDto, [
            'lsDoc' => $lsDoc,
            'action' => $this->generateUrl('framework_acl_edit', ['id' => $lsDoc->getId()]),
            'method' => 'POST',
        ]);
        $addAclUsernameDto = new AddAclUsernameDTO();
        $addUsernameForm = $this->createForm(AddAclUsernameType::class, $addAclUsernameDto);

        $addOrgUserForm->handleRequest($request);
        if ($ret = $this->handleOrgUserAdd($lsDoc, $addOrgUserForm)) {
            return $ret;
        }

        $addUsernameForm->handleRequest($request);
        if ($ret = $this->handleUsernameAdd($lsDoc, $addUsernameForm)) {
            return $ret;
        }

        $acls = $lsDoc->getDocAcls();
        $iterator = $acls->getIterator();
        $iterator->uasort(function (UserDocAcl $a, UserDocAcl $b) {
            return strcasecmp($a->getUser()->getUsername(), $b->getUser()->getUsername());
        });
        $acls = new ArrayCollection(iterator_to_array($iterator));

        $deleteForms = [];
        foreach ($acls as $acl) {
            /* @var UserDocAcl $acl */
            $deleteForms[$acl->getUser()->getId()] = $this->createDeleteForm($lsDoc, $acl->getUser())->createView();
        }

        if ('organization' === $lsDoc->getOwnedBy()) {
            $orgUsers = $lsDoc->getOrg()->getUsers();
        } else {
            $orgUsers = [];
        }

        return [
            'lsDoc' => $lsDoc,
            'aclCount' => $acls->count(),
            'acls' => $acls,
            'orgUsers' => $orgUsers,
            'addOrgUserForm' => $addOrgUserForm->createView(),
            'addUsernameForm' => $addUsernameForm->createView(),
            'deleteForms' => $deleteForms,
        ];
    }

    /**
     * @param LsDoc $lsDoc
     * @param Form $addOrgUserForm
     *
     * @return RedirectResponse|void
     */
    private function handleOrgUserAdd(LsDoc $lsDoc, Form $addOrgUserForm)
    {
        if ($addOrgUserForm->isSubmitted() && $addOrgUserForm->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $dto = $addOrgUserForm->getData();
            $dto->lsDoc = $lsDoc;
            $dto->access = UserDocAcl::DENY;
            $command = new AddAclUserCommand();
            try {
                $acl = $command->perform($dto, $em);
                $em->flush($acl);

                return $this->redirectToRoute('framework_acl_edit', ['id' => $lsDoc->getId()]);
            } catch (UniqueConstraintViolationException $e) {
                $error = new FormError('The username is already in your exception list.');
                $error->setOrigin($addOrgUserForm);
                $addOrgUserForm->addError($error);
            } catch (\InvalidArgumentException $e) {
                $error = new FormError($e->getMessage());
                $error->setOrigin($addOrgUserForm);
                $addOrgUserForm->addError($error);
            } catch (\Exception $e) {
                $error = new FormError('Unknown Error');
                $error->setOrigin($addOrgUserForm);
                $addOrgUserForm->addError($error);
            }
        }

        return;
    }

    /**
     * @param LsDoc $lsDoc
     * @param Form $addUsernameForm
     *
     * @return RedirectResponse|void
     */
    private function handleUsernameAdd(LsDoc $lsDoc, Form $addUsernameForm)
    {
        if ($addUsernameForm->isSubmitted() && $addUsernameForm->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $dto = $addUsernameForm->getData();
            $dto->lsDoc = $lsDoc;
            $dto->access = UserDocAcl::ALLOW;
            $command = new AddAclUsernameCommand();
            try {
                $acl = $command->perform($dto, $em);
                $em->flush($acl);

                return $this->redirectToRoute('framework_acl_edit', ['id' => $lsDoc->getId()]);
            } catch (UniqueConstraintViolationException $e) {
                $error = new FormError('The username is already in your exception list.');
                $error->setOrigin($addUsernameForm);
                $addUsernameForm->addError($error);
            } catch (\InvalidArgumentException $e) {
                $error = new FormError($e->getMessage());
                $error->setOrigin($addUsernameForm);
                $addUsernameForm->addError($error);
            } catch (\Exception $e) {
                //$error = new FormError($e->getMessage().' '.get_class($e));
                $error = new FormError('Unknown Error');
                $error->setOrigin($addUsernameForm);
                $addUsernameForm->addError($error);
            }
        }

        return;
    }

    /**
     * @Route("/{id}/acl/{targetUser}", name="framework_acl_remove")
     * @Method("DELETE")
     * @Security("is_granted('manage_editors', lsDoc)")
     *
     * @param Request $request
     * @param LsDoc $lsDoc
     * @param User $targetUser
     *
     * @return RedirectResponse
     */
    public function removeAclAction(Request $request, LsDoc $lsDoc, User $targetUser)
    {
        $form = $this->createDeleteForm($lsDoc, $targetUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $aclRepo = $em->getRepository('SaltUserBundle:UserDocAcl');
            $acl = $aclRepo->findByDocUser($lsDoc, $targetUser);
            if (!is_null($acl)) {
                $em->remove($acl);
                $em->flush($acl);
            }
        }

        return $this->redirectToRoute('framework_acl_edit', ['id' => $lsDoc->getId()]);
    }

    /**
     * Creates a form to delete a user entity.
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     * @param User $targetUser The user entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(LsDoc $lsDoc, User $targetUser)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('framework_acl_remove', ['id' => $lsDoc->getId(), 'targetUser' => $targetUser->getId()]))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
