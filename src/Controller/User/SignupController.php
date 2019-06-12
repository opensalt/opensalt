<?php

namespace App\Controller\User;

use App\Command\Email\SendSignupReceivedEmailCommand;
use App\Command\Email\SendAdminNotificationEmailCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\Type\SignupType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User\User;
use App\Entity\User\Organization;
use App\Command\User\AddUserCommand;
use App\Command\User\AddOrganizationCommand;
use App\Command\CommandDispatcherTrait;
use Qandidate\Bundle\ToggleBundle\Annotations\Toggle;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Signup Controller.
 *
 * @Route("/public/user")
 * @Toggle("create_account")
 */
class SignupController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var string
     */
    private $mailFromEmail;

    /**
     * @var string
     */
    private $kernelEnv;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, string $mailFromEmail = null, string $kernelEnv = null)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->mailFromEmail = $mailFromEmail;
        $this->kernelEnv = $kernelEnv;
    }

    /**
     * Creates a new user entity
     *
     * @Route("/signup", methods={"GET", "POST"}, name="public_user_signup")
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

        if ($form->isSubmitted()) {
            if ($form['org']->getData() === null && $form['newOrg']->getData() === null) {
                $form->addError(new FormError("New Organization field can't be blank"));
                $form->get('newOrg')->addError(new FormError("Can't be blank"));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $encryptedPassword = $this->passwordEncoder
                ->encodePassword($targetUser, $targetUser->getPlainPassword());

            if (null !== $form['newOrg']->getData()) {
                $org = new Organization();
                $org->setName($form['newOrg']->getData());

                $commandOrg = new AddOrganizationCommand($org);

                try {
                    $this->sendCommand($commandOrg);

                    $targetUser->setOrg($org);
                } catch (\Exception $e) {
                    if ($commandOrg->hasValidationErrors()) {
                        $errors = $commandOrg->getValidationErrors();
                        $form->addError(new FormError($errors[0]->getMessage()));
                        $form->get('newOrg')->addError(new FormError($errors[0]->getMessage()));
                    }
                }
            }

            try {
                $targetUser->setStatus(User::PENDING);
                $command = new AddUserCommand($targetUser, $encryptedPassword);
                $this->sendCommand($command);

                // Send email after user has been created
                try {
                    $command = new SendSignupReceivedEmailCommand($targetUser->getUsername());
                    $this->sendCommand($command);
                } catch (\Swift_RfcComplianceException $e) {
                    throw new \RuntimeException('A valid email address must be given.');
                } catch (\Exception $e) {
                    if ($command->hasValidationErrors()) {
                        $errors = $command->getValidationErrors();
                        $form->addError(new FormError($errors[0]->getMessage()));
                        $form->get('username')->addError(new FormError($errors[0]->getMessage()));
                    }
                    // Do not throw an error to the client if the email could not be sent
                }

                // send email to admin about this user creation
                // get public users username and org
                try {
                    $from_email = $this->mailFromEmail;
                    $command = new SendAdminNotificationEmailCommand($from_email, $targetUser->getUsername(), $targetUser->getOrg()->getName());
                    $this->sendCommand($command);
                } catch (\Swift_RfcComplianceException $e) {
                    throw new \RuntimeException('A valid email address must be given.');
                } catch (\Exception $e) {
                    // Do not throw an error to the client if the email could not be sent
                }

                return $this->redirectToRoute('lsdoc_index');
            } catch (\Exception $e) {
                if ('dev' === $this->kernelEnv) {
                    $form->addError(new FormError(get_class($e).': '.$e->getMessage()));
                } else {
                    $form->addError(new FormError('Sorry, an error occurred while creating your account.'));
                }
            }
        }

        return [
            'user' => $targetUser,
            'form' => $form->createView(),
        ];
    }
}
