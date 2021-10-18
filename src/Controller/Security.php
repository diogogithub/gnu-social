<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use App\Core\Log;
use App\Core\Router\Router;
use App\Core\VisibilityScope;
use App\Entity\Actor;
use App\Entity\Follow;
use App\Entity\LocalUser;
use App\Entity\Note;
use App\Security\Authenticator;
use App\Security\EmailVerifier;
use App\Util\Common;
use App\Util\Exception\NicknameEmptyException;
use App\Util\Exception\NicknameException;
use App\Util\Exception\NicknameInvalidException;
use App\Util\Exception\NicknameNotAllowedException;
use App\Util\Exception\NicknameTakenException;
use App\Util\Exception\NicknameTooLongException;
use App\Util\Exception\ServerException;
use App\Util\Form\FormFields;
use App\Util\Nickname;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use LogicException;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use function App\Core\I18n\_m;

class Security extends Controller
{
    /**
     * Log a user in
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('main_all');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $last_login_id = $authenticationUtils->getLastUsername();

        return [
            '_template' => 'security/login.html.twig',
            'last_login_id' => $last_login_id,
            'error' => $error,
            'notes_fn' => fn() => Note::getAllNotes(VisibilityScope::$instance_scope),
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
     * @param Request $request
     * @param GuardAuthenticatorHandler $guard_handler
     * @param Authenticator $authenticator
     *
     * @return null|array|Response
     * @throws NicknameEmptyException
     * @throws NicknameInvalidException
     * @throws NicknameNotAllowedException
     * @throws NicknameTakenException
     * @throws NicknameTooLongException
     * @throws ServerException
     * @throws NicknameException
     */
    public function register(Request                   $request,
                             GuardAuthenticatorHandler $guard_handler,
                             Authenticator             $authenticator): array|Response|null
    {
        $form = Form::create([
            ['nickname', TextType::class, [
                'label' => _m('Nickname'),
                'help' => _m('Your desired nickname (e.g., j0hnD03)'),
                'constraints' => [
                    new NotBlank(['message' => _m('Please enter a nickname')]),
                    new Length([
                        'min' => 1,
                        'minMessage' => _m(['Your nickname must be at least # characters long'], ['count' => 1]),
                        'max' => Nickname::MAX_LEN,
                        'maxMessage' => _m(['Your nickname must be at most # characters long'], ['count' => Nickname::MAX_LEN]),]),
                ],
                'block_name' => 'nickname',
                'label_attr' => ['class' => 'section-form-label'],
                'invalid_message' => _m('Nickname not valid. Please provide a valid nickname.'),
            ]],
            ['email', EmailType::class, [
                'label' => _m('Email'),
                'help' => _m('Desired email for this account (e.g., john@provider.com)'),
                'constraints' => [new NotBlank(['message' => _m('Please enter an email')])],
                'block_name' => 'email',
                'label_attr' => ['class' => 'section-form-label'],
                'invalid_message' => _m('Email not valid. Please provide a valid email.'),
            ]],
            FormFields::repeated_password(),
            ['register', SubmitType::class, ['label' => _m('Register')]],
        ], form_options: ['block_prefix' => 'register']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $data['password'] = $form->get('password')->getData();

            // TODO: ensure there's no user with this email registered already

            // Already used is checked below
            $sanitized_nickname = Nickname::normalize($data['nickname'], check_already_used: false, which: Nickname::CHECK_LOCAL_USER, check_is_allowed: false);

            try {
                // This already checks if the nickname is being used
                $actor = Actor::create(['nickname' => $sanitized_nickname]);
                $user = LocalUser::create([
                    'nickname' => $sanitized_nickname,
                    'outgoing_email' => $data['email'],
                    'incoming_email' => $data['email'],
                    'password' => LocalUser::hashPassword($data['password']),
                ]);
                DB::persistWithSameId(
                    $actor,
                    $user,
                    // Self follow
                    fn(int $id) => DB::persist(Follow::create(['follower' => $id, 'followed' => $id])),
                );

                Event::handle('SuccessfulLocalUserRegistration', [$actor, $user]);

                DB::flush();
                // @codeCoverageIgnoreStart
            } catch (UniqueConstraintViolationException $e) {
                // _something_ was duplicated, but since we already check if nickname is in use, we can't tell what went wrong
                $e = 'An error occurred while trying to register';
                Log::critical($e . " with nickname: '{$sanitized_nickname}' and email '{$data['email']}'");
                throw new ServerException(_m($e));
            }
            // @codeCoverageIgnoreEnd

            // generate a signed url and email it to the user
            if ($_ENV['APP_ENV'] !== 'dev' && Common::config('site', 'use_email')) {
                // @codeCoverageIgnoreStart
                EmailVerifier::sendEmailConfirmation($user);
                // @codeCoverageIgnoreEnd
            } else {
                $user->setIsEmailVerified(true);
            }

            return $guard_handler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main', // firewall name in security.yaml
            );
        }

        return [
            '_template' => 'security/register.html.twig',
            'registration_form' => $form->createView(),
            'notes_fn' => fn() => Note::getAllNotes(VisibilityScope::$instance_scope),
        ];
    }
}
