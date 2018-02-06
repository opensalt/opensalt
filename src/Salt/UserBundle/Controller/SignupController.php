<?php

namespace Salt\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Salt\UserBundle\Form\Type\SignupType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Salt\UserBundle\Entity\User;
use Salt\UserBundle\Entity\Organization;
use App\Command\User\AddUserCommand;
use App\Command\User\AddOrganizationCommand;
use App\Command\CommandDispatcherTrait;
use Qandidate\Bundle\ToggleBundle\Annotations\Toggle;
use Symfony\Component\Form\FormError;

/**
 * Signup Controller.
 *
 * @Route("public/user")
 * @Toggle("create_account")
 */
class SignupController extends Controller
{
    use CommandDispatcherTrait;

    /**
     * Creates a new user entity
     *
     * @Route("/signup", name="public_user_signup")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function signupAction(Request $request)
    {
        $targetUser = new User();
        $form = $this->createForm(SignupType::class, $targetUser, ['validation_groups' => ['registration']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $encryptedPassword = $this->get('security.password_encoder')
                ->encodePassword($targetUser, $targetUser->getPlainPassword());

            if (!is_null($form['new_org']->getData())) {
                $org = new Organization();
                $org->setName($form['new_org']->getData());

                try {
                    $commandOrg = new AddOrganizationCommand($org);
                    $this->sendCommand($commandOrg);

                    $targetUser->setOrg($org);
                } catch (\Exception $e) {
                    $form->addError(new FormError($e->getMessage()));
                }
            }

            try {
                $command = new AddUserCommand($targetUser, $encryptedPassword);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdoc_index');
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return [
            'user' => $targetUser,
            'form' => $form->createView(),
        ];
    }
}
