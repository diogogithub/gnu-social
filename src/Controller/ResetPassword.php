<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Entity\LocalUser;
use App\Security\EmailVerifier;
use App\Util\Exception\ClientException;
use App\Util\Exception\RedirectException;
use App\Util\Form\FormFields;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

/**
 * Send password reset emails to users
 * TODO: As we don't have email services setup yet, this won't be tested right now
 *
 * @codeCoverageIgnore
 */
class ResetPassword extends Controller
{
    use ResetPasswordControllerTrait;

    /**
     * Display & process form to request a password reset.
     */
    public function requestPasswordReset(Request $request)
    {
        $form = Form::create([
            ['email', EmailType::class,  ['label' => _m('Email'), 'constraints' => [new NotBlank(['message' => _m('Please enter an email')])]]],
            ['password_reset_request', SubmitType::class, ['label' => _m('Submit request')]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return EmailVerifier::processSendingPasswordResetEmail($form->get('email')->getData(), $this);
        }

        return [
            '_template'           => 'reset_password/request.html.twig',
            'password_reset_form' => $form->createView(),
        ];
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    public function checkEmail()
    {
        // We prevent users from directly accessing this page
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            throw new RedirectException('request_reset_password');
        }

        return [
            '_template'  => 'reset_password/check_email.html.twig',
            'resetToken' => $resetToken,
        ];
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    public function reset(Request $request, ?string $token = null)
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);
            throw new RedirectException('reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw new ClientException(_m('No reset password token found in the URL or in the session'));
        }

        try {
            $user = EmailVerifier::validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', _m('There was a problem validating your reset request - {reason}', ['reason' => $e->getReason()]));
            throw new RedirectException('request_reset_password');
        }

        // The token is valid; allow the user to change their password.
        $form = Form::create([
            FormFields::repeated_password(),
            ['password_reset', SubmitType::class, ['label' => _m('Change password')]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            EmailVerifier::removeResetRequest($token);

            $user->setPassword(LocalUser::hashPassword($form->get('password')->getData()));
            DB::flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            throw new RedirectException('main_all');
        }

        return [
            '_template' => 'reset_password/reset.html.twig',
            'resetForm' => $form->createView(),
        ];
    }

    public function setInSession(ResetPasswordToken $reset_token)
    {
        $this->setTokenObjectInSession($reset_token);
    }
}
