<?php

declare(strict_types=1);

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

namespace App\Security;

use App\Core\Router\Router;
use App\Entity\LocalUser;
use App\Util\Common;
use App\Util\Exception\NoSuchActorException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\ServerException;
use App\Util\Nickname;
use Stringable;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use function App\Core\I18n\_m;

/**
 * User authenticator
 *
 * @category  Authentication
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Authenticator extends AbstractFormLoginAuthenticator implements AuthenticatorInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'security_login';

    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    /**
     * @return array<string, string>
     */
    public function getCredentials(Request $request): array
    {
        return [
            'nickname_or_email' => $request->request->get('nickname_or_email'),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
    }

    /**
     * Get a user given credentials and a CSRF token
     *
     * @param array<string, string> $credentials result of self::getCredentials
     * @param UserProviderInterface $userProvider
     * @return ?LocalUser
     * @throws NoSuchActorException
     * @throws ServerException
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?LocalUser
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }
        $user = null;
        try {
            if (Common::isValidEmail($credentials['nickname_or_email'])) {
                $user = LocalUser::getByEmail($credentials['nickname_or_email']);
            } elseif (Nickname::isValid($credentials['nickname_or_email'])) {
                $user = LocalUser::getByNickname($credentials['nickname_or_email']);
            }
            if (is_null($user)) {
                throw new NoSuchActorException('No such local user.');
            }
            $credentials['nickname'] = $user->getNickname();
        } catch (NoSuchActorException|NotFoundException) {
            throw new CustomUserMessageAuthenticationException(
                _m('Invalid login credentials.'),
            );
        }
        return $user;
    }

    /**
     * @param array<string, string> $credentials result of self::getCredentials
     * @param LocalUser $user
     * @return bool
     * @throws ServerException
     */
    public function checkCredentials($credentials, $user): bool
    {
        if (!$user->checkPassword($credentials['password'])) {
            throw new CustomUserMessageAuthenticationException(_m('Invalid login credentials.'));
        } else {
            return true;
        }
    }

    /**
     * After a successful login, redirect user to the path saved in their session or to the root of the website
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): RedirectResponse
    {
        $nickname = $token->getUser();
        if ($nickname instanceof Stringable) {
            $nickname = (string)$nickname;
        } elseif ($nickname instanceof UserInterface) {
            $nickname = $nickname->getUserIdentifier();
        }

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $nickname,
        );

        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse(Router::url('main_all'));
    }

    protected function getLoginUrl(): string
    {
        return Router::url(self::LOGIN_ROUTE);
    }
}
