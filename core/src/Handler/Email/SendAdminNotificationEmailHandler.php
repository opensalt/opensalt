<?php

namespace App\Handler\Email;

use App\Command\Email\AbstractSendEmailCommand;
use App\Command\Email\SendAdminNotificationEmailCommand;
use Novaway\Bundle\FeatureFlagBundle\Manager\FeatureManager;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

class SendAdminNotificationEmailHandler extends AbstractEmailHandler
{
    public function __construct(
        ValidatorInterface $validator,
        FeatureManager $featureManager,
        MailerInterface $mailer,
        private readonly Environment $templating,
        ?string $mailFromEmail = null,
    ) {
        parent::__construct($validator, $featureManager, $mailer, $mailFromEmail);
    }

    protected function configureMessage(Email $email, AbstractSendEmailCommand $command): void
    {
        /** @var SendAdminNotificationEmailCommand $command */
        $userName = $command->getUsername();
        $organization = $command->getOrganization();
        $email
            ->subject('An account was created that needs to be approved')
            ->html(
                $this->templating->render(
                    // app/Resources/views/emails/admin_notification.html.twig
                    'emails/admin_notification.html.twig',
                    [
                        'username' => $userName,
                        'organization' => $organization,
                    ] // pass values to the template
                ),
                'text/html'
            );
    }
}
