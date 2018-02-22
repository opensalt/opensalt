<?php

namespace App\Handler\Email;

class SendAdminNotificationEmailHandler extends AbstractEmailHandler
{
    protected function configureMessage(\Swift_Message $email): void
    {
      $userName = 'staticUser';
      $organization = 'staticOrg';
        $email
            ->setSubject('An account was created that needs to be approved')
            ->setBody(
              $this->templating->render(
                  // app/Resources/views/Emails/admin_notification.html.twig
                  'Emails/admin_notification.html.twig',
                  array(
                    'username' => $userName,
                    'organization' => $organization
                  ) // pass values to the template
              ),
              'text/html'
            )
        ;
    }
}
