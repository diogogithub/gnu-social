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

namespace App\Util\Exception;

use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Router\Router;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectException extends Exception
{
    public ?RedirectResponse $redirect_response = null;

    /**
     * Used for responding to a request with a redirect. Either
     * generates a url from a $route_id_or_path and $params or fully formed,
     * from $url. Prevents open redirects, unless $allow_open_redirect
     */
    public function __construct(string $route_id_or_path = '', array $params = [], string $message = '', int $code = 302, ?string $url = null, bool $allow_open_redirect = false, ?Exception $previous_exception = null)
    {
        if (!empty($route_id_or_path) || !empty($url)) {
            if ($route_id_or_path[0] === '/') {
                $url = "https://{$_ENV['SOCIAL_DOMAIN']}{$route_id_or_path}";
            } else {
                $url ??= Router::url($route_id_or_path, $params, Router::ABSOLUTE_PATH); // Absolute path doesn't include host
                if (!$allow_open_redirect) {
                    if (Router::isAbsolute($url)) {
                        Log::warning("A RedirectException that shouldn't allow open redirects attempted to redirect to {$url}");
                        throw new ServerException(_m('Can not redirect to outside the website from here'), 5400); // 500 Internal server error (likely a bug)
                    }
                }
            }
            $this->redirect_response = new RedirectResponse($url);
        }
        parent::__construct($message, $code, $previous_exception);
    }
}
