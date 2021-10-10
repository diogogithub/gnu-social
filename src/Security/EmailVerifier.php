<?php

declare(strict_types = 1);

namespace App\Security;

use App\Controller\ResetPassword;
use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Entity\LocalUser;
use App\Util\Common;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\RedirectException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

abstract class EmailVerifier
{
    private static ?MailerInterface $mailer_helper;
    private static ?VerifyEmailHelperInterface $verify_email_helper;
    private static ?ResetPasswordHelperInterface $reset_password_helper;

    public static function setEmailHelpers(MailerInterface $mailer, VerifyEmailHelperInterface $email_helper, ResetPasswordHelperInterface $reset_helper)
    {
        self::$mailer_helper         = $mailer;
        self::$verify_email_helper   = $email_helper;
        self::$reset_password_helper = $reset_helper;
    }

    public static function validateTokenAndFetchUser(string $token)
    {
        return self::$reset_password_helper->validateTokenAndFetchUser($token);
    }

    public static function removeResetRequest(string $token): void
    {
        self::$reset_password_helper->removeResetRequest($token);
    }

    public static function sendEmailConfirmation(LocalUser $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(Common::config('site', 'email'), Common::config('site', 'nickname')))
            ->to($user->getOutgoingEmail())
            ->subject(_m('Please Confirm your Email'))
            ->htmlTemplate('security/confirmation_email.html.twig');

        $signatureComponents = self::$verify_email_helper->generateSignature(
            'verify_email',
            $user->getId(),
            $user->getOutgoingEmail(),
        );

        $context              = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAt'] = $signatureComponents->getExpiresAt();

        $email->context($context);

        self::send($email);
    }

    public static function send($email): void
    {
        self::$mailer_helper->send($email);
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, LocalUser $user): void
    {
        self::$verify_email_helper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getOutgoingEmail());
        $user->setIsEmailVerified(true);
        DB::persist($user);
        DB::flush();
    }

    public static function processSendingPasswordResetEmail(string $emailFormData, ResetPassword $controller)
    {
        try {
            $user        = DB::findOneBy('local_user', ['outgoing_email' => $emailFormData]);
            $reset_token = self::$reset_password_helper->generateResetToken($user);
            // Found a user
        } catch (NotFoundException|ResetPasswordExceptionInterface) {
            // Not found, do not reveal whether a user account was found or not.
            throw new RedirectException('check_email');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('foo@email.com', 'FOO NAME'))
            ->to($user->getOutgoingEmail())
            ->subject('Your password reset request')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $reset_token,
            ]);

        self::send($email);

        // Store the token object in session for retrieval in check-email route.
        $controller->setInSession($reset_token);

        throw new RedirectException('check_email');
    }
}
