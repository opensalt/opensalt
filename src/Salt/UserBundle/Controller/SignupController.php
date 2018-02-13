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

                // check to see if the env var is set to "true" mailer
                if ($this->getParameter('USE_MAIL_FEATURE') == 'always-active') {
                  // $fromEmail = getenv('MAIL_FEATURE_FROM_EMAIL');
                  $fromEmail = $this->getParameter('MAIL_FEATURE_FROM_EMAIL');
                  // email the new user
                  $email = $targetUser->getUsername();
                  $message = (new \Swift_Message('Hello Email'))
                  ->setFrom($fromEmail)
                  ->setTo($email)
                  ->setSubject('Your account has been created')
                  ->setBody('Thank you! Your account has been created and you will be contacted in 2 business days when it is active.');

                  $this->get('mailer')->send($message);
                }

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
