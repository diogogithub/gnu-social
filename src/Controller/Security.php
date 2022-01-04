<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\UserRoles;
use App\Entity\Actor;
use App\Entity\Feed;
use App\Entity\LocalUser;
use App\Security\Authenticator;
use App\Security\EmailVerifier;
use App\Util\Common;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\EmailTakenException;
use App\Util\Exception\NicknameEmptyException;
use App\Util\Exception\NicknameException;
use App\Util\Exception\NicknameInvalidException;
use App\Util\Exception\NicknameNotAllowedException;
use App\Util\Exception\NicknameTakenException;
use App\Util\Exception\NicknameTooLongException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\ServerException;
use App\Util\Form\FormFields;
use App\Util\Nickname;
use Component\Language\Entity\ActorLanguage;
use Component\Subscription\Entity\Subscription;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use LogicException;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class Security extends Controller
{
    /**
     * Log a user in
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        // Skip if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('root');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $last_login_id = $authenticationUtils->getLastUsername();

        return [
            '_template'     => 'security/login.html.twig',
            'last_login_id' => $last_login_id,
            'error'         => $error,
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    public function logout()
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Register a user, making sure the nickname is not reserved and
     * possibly sending a confirmation email
     *
     * @throws DuplicateFoundException
     * @throws EmailTakenException
     * @throws EmailTakenException
     * @throws NicknameEmptyException
     * @throws NicknameException
     * @throws NicknameInvalidException
     * @throws NicknameNotAllowedException
     * @throws NicknameTakenException
     * @throws NicknameTooLongException
     * @throws ServerException
     */
    public function register(
        Request $request,
        UserPasswordHasherInterface $user_password_hasher,
        Authenticator $authenticator,
        GuardAuthenticatorHandler $guard,
    ): array|Response {
        $form = Form::create([
            ['nickname', TextType::class, [
                'label'       => _m('Nickname'),
                'help'        => _m('Your desired nickname (e.g., j0hnD03)'),
                'constraints' => [
                    new NotBlank(['message' => _m('Please enter a nickname')]),
                    new Length([
                        'max'        => Nickname::MAX_LEN,
                        'maxMessage' => _m(['Your nickname must be at most # characters long'], ['count' => Nickname::MAX_LEN]),
                    ]),
                ],
                'block_name'      => 'nickname',
                'label_attr'      => ['class' => 'section-form-label'],
                'invalid_message' => _m('Nickname not valid. Please provide a valid nickname.'),
            ]],
            ['email', EmailType::class, [
                'label'           => _m('Email'),
                'help'            => _m('Desired email for this account (e.g., john@provider.com)'),
                'constraints'     => [new NotBlank(['message' => _m('Please enter an email')])],
                'block_name'      => 'email',
                'label_attr'      => ['class' => 'section-form-label'],
                'invalid_message' => _m('Email not valid. Please provide a valid email.'),
                'attr'            => ['autocomplete' => 'email'],
            ]],
            FormFields::repeated_password(['attr' => ['autocomplete' => 'new-password']]),
            ['register', SubmitType::class, ['label' => _m('Register')]],
        ], form_options: ['block_prefix' => 'register']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data             = $form->getData();
            $data['password'] = $form->get('password')->getData();

            // Already used is checked below
            $nickname = Nickname::normalize($data['nickname'], check_already_used: false, which: Nickname::CHECK_LOCAL_USER, check_is_allowed: false);

            try {
                $found_user = DB::findOneBy('local_user', ['or' => ['nickname' => $nickname, 'outgoing_email' => $data['email']]]);
                if ($found_user->getNickname() === $nickname) {
                    throw new NicknameTakenException($found_user->getActor());
                } elseif ($found_user->getOutgoingEmail() === $data['email']) {
                    throw new EmailTakenException($found_user->getActor());
                }
                unset($found_user);
            } catch (NotFoundException) {
                // continue
            }

            try {
                // This already checks if the nickname is being used
                $actor = Actor::create([
                    'nickname' => $nickname,
                    'is_local' => true,
                    'type'     => Actor::PERSON,
                    'roles'    => UserRoles::USER,
                ]);
                $user = LocalUser::create([
                    'nickname'       => $nickname,
                    'outgoing_email' => $data['email'],
                    'incoming_email' => $data['email'],
                ]);
                $user->setPassword($user_password_hasher->hashPassword($user, $data['password']));
                DB::persistWithSameId(
                    $actor,
                    $user,
                    function (int $id) use ($user) {
                        // Self subscription for the Home feed and alike
                        DB::persist(Subscription::create(['subscriber_id' => $id, 'subscribed_id' => $id]));
                        Feed::createDefaultFeeds($id, $user);
                        DB::persist(ActorLanguage::create([
                            'actor_id'    => $id,
                            'language_id' => Common::currentLanguage()->getId(),
                            'ordering'    => 1,
                        ]));
                    },
                );

                Event::handle('SuccessfulLocalUserRegistration', [$actor, $user]);

                DB::flush();
                // @codeCoverageIgnoreStart
            } catch (UniqueConstraintViolationException $e) {
                // _something_ was duplicated, but since we already check if nickname is in use, we can't tell what went wrong
                $m = 'An error occurred while trying to register';
                Log::critical($m . " with nickname: '{$nickname}' and email '{$data['email']}'");
                throw new ServerException(_m($m), previous: $e);
            }
            // @codeCoverageIgnoreEnd

            // generate a signed url and email it to the user
            if ($_ENV['APP_ENV'] !== 'dev' && Common::config('site', 'use_email')) {
                // @codeCoverageIgnoreStart
                // TODO: Implement send confirmation email
                // (new EmailVerifier())->sendEmailConfirmation($user);
            // @codeCoverageIgnoreEnd
            } else {
                $user->setIsEmailVerified(true);
            }

            return $guard->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main',
            );
        }

        return [
            '_template'         => 'security/register.html.twig',
            'registration_form' => $form->createView(),
        ];
    }
}
