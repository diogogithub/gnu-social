<?php

namespace App\Security;

use App\Core\DB\DB;
use App\Core\Mailer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

abstract class EmailVerifier
{
    private static ?VerifyEmailHelperInterface $verify_email_helper;
    public function setVerifyEmailHelper(VerifyEmailHelperInterface $helper)
    {
        self::$verifyEmailHelper = $helper;
    }

    public static function sendEmailConfirmation(UserInterface $user): void
    {
        $email = (new TemplatedEmail())
               ->from(new Address(Common::config('site', 'email'), Common::config('site', 'nickname')))
               ->to($user->getOutgoingEmail())
               ->subject(_m('Please Confirm your Email'))
               ->htmlTemplate('security/confirmation_email.html.twig');

        $signatureComponents = self::$verify_email_helper->generateSignature(
            'verify_email',
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
