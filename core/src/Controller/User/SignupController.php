<?php

namespace App\Controller\User;

use App\Command\CommandDispatcherTrait;
use App\Command\Email\SendAdminNotificationEmailCommand;
use App\Command\Email\SendSignupReceivedEmailCommand;
use App\Command\User\AddOrganizationCommand;
use App\Command\User\AddUserCommand;
use App\Entity\User\Organization;
use App\Entity\User\User;
use App\Form\Type\SignupType;
use Novaway\Bundle\FeatureFlagBundle\Attribute\IsFeatureEnabled;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/public/user')]
#[IsFeatureEnabled(name: 'create_account')]
class SignupController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordEncoder,
        private readonly ?string $mailFromEmail = null,
        private readonly ?string $kernelEnv = null,
    ) {
    }

    /**
     * Creates a new user entity.
     */
    #[Route(path: '/signup', name: 'public_user_signup', methods: ['GET', 'POST'])]
    public function signup(Request $request): Response
    {
        $targetUser = new User();
        $form = $this->createForm(SignupType::class, $targetUser, ['validation_groups' => ['registration']]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (null === $form['org']->getData() && null === $form['newOrg']->getData()) {
                $form->addError(new FormError("New Organization field can't be blank"));
                $form->get('newOrg')->addError(new FormError("Can't be blank"));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $encryptedPassword = $this->passwordEncoder
                ->hashPassword($targetUser, $targetUser->getPlainPassword());

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
                    $command = new SendSignupReceivedEmailCommand($targetUser->getUserIdentifier());
                    $this->sendCommand($command);
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
                    $command = new SendAdminNotificationEmailCommand($from_email, $targetUser->getUserIdentifier(), $targetUser->getOrg()->getName());
                    $this->sendCommand($command);
                } catch (\Exception $e) {
                    // Do not throw an error to the client if the email could not be sent
                }

                return $this->redirectToRoute('lsdoc_index');
            } catch (\Exception $e) {
                if ('dev' === $this->kernelEnv) {
                    $form->addError(new FormError($e::class.': '.$e->getMessage()));
                } else {
                    $form->addError(new FormError('Sorry, an error occurred while creating your account.'));
                }
            }
        }

        return $this->render('user/signup/signup.html.twig', [
            'user' => $targetUser,
            'form' => $form->createView(),
        ]);
    }
}
