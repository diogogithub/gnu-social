<?php

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

use App\Core\Router\Router;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectException extends Exception
{
    public ?RedirectResponse $redirect_response = null;

    public function __construct(string $url_id = '', $message = '', $code = 302, Exception $previous_exception = null)
    {
        if (!empty($url_id)) {
            $this->redirect_response = new RedirectResponse(Router::url($urlid));
        }
        parent::__construct($message, $code, $previous_exception);
    }
}
