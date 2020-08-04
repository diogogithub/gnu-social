<?php
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

/**
 * Client error action.
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Zach Copley <zach@status.net>
 * @copyright 2008-2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Class for displaying HTTP client errors
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ClientErrorAction extends ErrorAction
{
    public static $status = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed'
    ];

    public function __construct($message = 'Error', $code = 400)
    {
        parent::__construct($message, $code);
        $this->default = 400;

        if (!$this->code || $this->code < 400 || $this->code > 499) {
            $this->code = $this->default;
        }
        if (!$this->message) {
            $this->message = "Client Error $this->code";
        }
    }

    /**
     *  To specify additional HTTP headers for the action
     *
     *  @return void
     */
    public function extraHeaders()
    {
        http_response_code($this->code);
    }

    /**
     * Page title.
     *
     * @return page title
     */

    public function title()
    {
        return @self::$status[$this->code];
    }
}
