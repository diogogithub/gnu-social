<?php

declare(strict_types = 1);

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

use App\Core\DB\DB;
use App\Util\Exception\NoSuchActorException;
use App\Util\Exception\NotFoundException;
use function App\Core\I18n\_m;
use App\Entity\LocalUser;
use App\Entity\User;
use App\Util\Nickname;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

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
class Authenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'security_login';

    private $urlGenerator;
    private $csrfTokenManager;

    public function __construct(UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->urlGenerator     = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function supports(Request $request)
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
        return [
            'nickname_or_email'   => $request->request->get('nickname_or_email'),
            'password'   => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        try {
            if (filter_var($credentials['nickname_or_email'], FILTER_VALIDATE_EMAIL) !== false) {
                $user = LocalUser::getByEmail($credentials['nickname_or_email']);
            } else {
                $user = LocalUser::getWithPK(['nickname' => Nickname::normalize($credentials['nickname_or_email'], check_already_used: false)]);
            }
            if ($user === null) {
                throw new NoSuchActorException('No such local user.');
            }
            $credentials['nickname'] = $user->getNickname();
        } catch (Exception) {
            throw new CustomUserMessageAuthenticationException(
                _m('Invalid login credentials.'),
            );
        }

        return $user;
    }

    public function checkCredentials($credentials, LocalUser $user)
    {
        if (!$user->checkPassword($credentials['password'])) {
            throw new CustomUserMessageAuthenticationException(_m('Invalid login credentials.'));
        } else {
            return true;
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $token->getUser()->getNickname()
        );
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('main_all'));
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
