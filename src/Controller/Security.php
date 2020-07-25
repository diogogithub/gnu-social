<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Entity\LocalUser;
use App\Entity\Profile;
use App\Security\Authenticator;
use App\Security\EmailVerifier;
use app\Util\Common;
use App\Util\Nickname;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class Security extends Controller
{
    public function login(AuthenticationUtils $authenticationUtils)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('main_all');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $last_username = $authenticationUtils->getLastUsername();

        return ['_template' => 'security/login.html.twig', 'last_username' => $last_username, 'error' => $error];
    }

    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    public function register(Request $request,
                             EmailVerifier $email_verifier,
                             GuardAuthenticatorHandler $guard_handler,
                             Authenticator $authenticator)
    {
        $form = Form::create([
            ['nickname', TextType::class, [
                'label'       => _m('Nickname'),
                'constraints' => [new Length([
                    'min'        => 1,
                    'minMessage' => _m('Your password should be at least {{ limit }} characters long'),
                    'max'        => 64,
                    'maxMessage' => _m('Your password should be at most {{ limit }} characters long'), ]),
                ],
            ]],
            ['email', EmailType::class, ['label' => _m('Email')]],
            ['password', PasswordType::class, [
                'label'       => _m('Password'),
                'mapped'      => false,
                'constraints' => [
                    new NotBlank(['message' => _m('Please enter a password')]),
                    new Length(['min' => 6, 'minMessage' => _m('Your password should be at least {{ limit }} characters'), 'max' => 60]),
                ],
            ]],
            ['register', SubmitType::class, ['label' => _m('Register')]],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data             = $form->getData();
            $data['password'] = $form->get('password')->getData();

            $valid_nickname = Nickname::isValid($data['nickname'], Nickname::CHECK_USED);
            if (!$valid_nickname) {
                throw new \Exception(_m('Invalid nickname'));
            }

            $profile = new Profile($data['nickname']);
            $user    = new LocalUser($data['nickname'], $data['email'], $data['password']);

            DB::persist($user);
            DB::persist($profile);
            DB::flush();

            // generate a signed url and email it to the user
            if (Common::config('site', 'use_email')) {
                $email_verifier->sendEmailConfirmation(
                    'verify_email',
                    $user,
                    (new TemplatedEmail())
                    ->from(new Address(Common::config('site', 'email'), Common::config('site', 'nickname')))
                    ->to($user->getOutgoingEmail())
                    ->subject(_m('Please Confirm your Email'))
                    ->htmlTemplate('security/confirmation_email.html.twig')
                );
            } else {
                $user->setIsEmailVerified(true);
            }

            return $guard_handler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main' // firewall name in security.yaml
            );
        }

        return [
            '_template'         => 'security/register.html.twig',
            'registration_form' => $form->createView(),
        ];

        return ['_template' => 'security/register.html.twig', 'form' => $form->createView()];
    }
}
