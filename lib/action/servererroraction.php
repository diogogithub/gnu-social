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
 * Server error action.
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Zach Copley <zach@status.net>
 * @copyright 2008, 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Class for displaying HTTP server errors
 *
 * Note: The older util.php class simply printed a string, but the spec
 * says that 500 errors should be treated similarly to 400 errors, and
 * it's easier to give an HTML response.  Maybe we can customize these
 * to display some funny animal cartoons.  If not, we can probably role
 * these classes up into a single class.
 *
 * See: http://tools.ietf.org/html/rfc2616#section-10
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ServerErrorAction extends ErrorAction
{
    public static $status = [
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ];

    public function __construct($message = 'Error', $code = 500, $ex = null)
    {
        parent::__construct($message, $code);

        $this->default = 500;

        if (!$this->code || $this->code < 500 || $this->code > 599) {
            $this->code = $this->default;
        }

        if (!$this->message) {
            $this->message = "Server Error $this->code";
        }

        // Server errors must be logged.
        $log = "ServerErrorAction: $code $message";
        if ($ex) {
            $log .= "\n" . $ex->getTraceAsString();
        }
        common_log(LOG_ERR, $log);

        $this->showPage();
    }

    /**
     *  To specify additional HTTP headers for the action
     *
     * @return void
     */
    public function extraHeaders()
    {
        http_response_code($this->code);
    }

    /**
     * Page title.
     *
     * @return string page title
     */

    public function title()
    {
        return self::$status[$this->code];
    }
}
