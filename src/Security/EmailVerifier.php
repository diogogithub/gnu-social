<?php

namespace App\Security;

use App\Core\DB\DB;
use App\Core\Mailer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    private $verify_email_helper;

    public function __construct(VerifyEmailHelperInterface $helper)
    {
        $this->verifyEmailHelper = $helper;
    }

    public function sendEmailConfirmation(string $verify_email_route_name, UserInterface $user, TemplatedEmail $email): void
    {
        $signatureComponents = $this->verify_email_helper->generateSignature(
            $verify_email_route_name,
            $user->getId(),
            $user->getOutgoingEmail()
        );

        $context              = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAt'] = $signatureComponents->getExpiresAt();

        $email->context($context);

        Mailer::send($email);
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, UserInterface $user): void
    {
        $this->verify_email_helper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getOutgoingEmail());

        $user->setIsEmailVerified(true);

        DB::persist($user);
        DB::flush();
    }
}
