<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\User\User;
use App\Form\DTO\MfaCodeDTO;
use App\Form\Type\MfaCodeDTOType;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MfaController extends AbstractController
{
    public const string SESSION_PENDING_SECRET = 'pending_totp_secret';

    public function __construct(
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/authentication/2fa/enable', name: 'app_2fa_enable')]
    #[IsGranted('ROLE_USER')]
    public function enable2fa(Request $request, #[CurrentUser] User $user): Response
    {
        if ($user->isTotpAuthenticationEnabled()) {
            return $this->redirectToRoute('salt_index');
        }

        $code = new MfaCodeDTO();
        $form = $this->createForm(MfaCodeDTOType::class, $code);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $session = $request->getSession();
            $pendingSecret = $session->get(self::SESSION_PENDING_SECRET);

            if (null === $pendingSecret) {
                $this->addFlash('warning', 'Your 2FA setup session expired. Please try again.');

                return $this->redirectToRoute('app_2fa_enable');
            }

            $user->setTotpSecret($pendingSecret);
            $user->setIsTotpEnabled(true);
            $this->entityManager->flush();

            $session->remove(self::SESSION_PENDING_SECRET);

            return $this->redirectToRoute('salt_index');
        }

        if (!$form->isSubmitted()) {
            $secret = Base32::encodeUpperUnpadded(random_bytes(16));
            $user->setTotpSecret($secret);

            $request->getSession()->set(self::SESSION_PENDING_SECRET, $secret);

            $qrCode = new QrCode(
                data: $this->totpAuthenticator->getQRContent($user),
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255),
            );
            $writer = new PngWriter();
            $uri = $writer->write($qrCode)->getDataUri();

            return $this->render('user/mfa/enable2fa.html.twig', [
                'dataUri' => $uri,
                'code' => $secret,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('user/mfa/enable2fa.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/authentication/2fa/reset', name: 'app_2fa_reset_confirm', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function reset2faConfirm(#[CurrentUser] User $user): Response
    {
        if ($user->isTotpAuthenticationEnabled()) {
            return $this->render('user/mfa/reset2fa.html.twig', [
            ]);
        }

        return $this->redirectToRoute('salt_index');
    }

    #[Route('/authentication/2fa/reset', name: 'app_2fa_reset', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function reset2fa(#[CurrentUser] User $user): Response
    {
        if ($user->isTotpAuthenticationEnabled()) {
            $user->setIsTotpEnabled(false);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_2fa_enable');
        }

        return $this->redirectToRoute('salt_index');
    }
}
