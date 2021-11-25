<?php

declare(strict_types = 1);

namespace App\Security;

use App\Core\DB\DB;
use App\Entity\LocalUser;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

abstract class EmailVerifier
{
    private static $verifyEmailHelper;
    private static $mailer;

    public static function setHelpers(VerifyEmailHelperInterface $helper, MailerInterface $mailer)
    {
        self::$verifyEmailHelper = $helper;
        self::$mailer            = $mailer;
    }

    /**
     * @param LocalUser $user
     */
    public static function sendEmailConfirmation(string $verifyEmailRouteName, UserInterface $user, TemplatedEmail $email): void
    {
        $signatureComponents = self::$verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            $user->getId(),
            $user->getOutgoingEmail(),
            ['id' => $user->getId()],
        );

        $context                         = $email->getContext();
        $context['signedUrl']            = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey']  = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);

        self::$mailer->send($email);
    }

    /**
     * @param LocalUser $user
     *
     * @throws VerifyEmailExceptionInterface
     */
    public static function handleEmailConfirmation(Request $request, UserInterface $user): void
    {
        self::$verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getOutgoingEmail());
        $user->setIsEmailVerified(true);
        DB::persist($user);
        DB::flush();
    }
}
