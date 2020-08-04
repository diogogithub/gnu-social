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
 * An action that requires an API key
 *
 * @category  DomainStatusNetwork
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * An action that requires an API key
 *
 * @category  General
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class GlobalApiAction extends Action
{
    public $email;

    /**
     * Check for an API key, and throw an exception if it's not set
     *
     * @param array $args URL and POST params
     *
     * @return boolean continuation flag
     */

    public function prepare(array $args = [])
    {
        GNUsocial::setApi(true); // reduce exception reports to aid in debugging

        parent::prepare($args);

        if (!common_config('globalapi', 'enabled')) {
            throw new ClientException(_('Global API not enabled.'), 403);
        }

        $apikey = $this->trimmed('apikey');

        if (empty($apikey)) {
            throw new ClientException(_('No API key.'), 403);
        }

        $expected = common_config('globalapi', 'key');

        if ($expected != $apikey) {
            // FIXME: increment a counter by IP address to prevent brute-force
            // attacks on the key.
            throw new ClientException(_('Bad API key.'), 403);
        }

        $email = common_canonical_email($this->trimmed('email'));

        if (empty($email)) {
            throw new ClientException(_('No email address.'));
        }

        if (!Validate::email($email, common_config('email', 'check_domain'))) {
            throw new ClientException(_('Invalid email address.'));
        }

        $this->email = $email;

        return true;
    }

    public function showError($message, $code = 400)
    {
        $this->showOutput(array('error' => $message), $code);
    }

    public function showSuccess($values = null, $code = 200)
    {
        if (empty($values)) {
            $values = array();
        }
        $values['success'] = 1;
        $this->showOutput($values, $code);
    }

    public function showOutput($values, $code)
    {
        if (
            !array_key_exists($code, ClientErrorAction::$status)
            && !array_key_exists($code, ServerErrorAction::$status)
        ) {
            // bad code!
            $code = 500;
        }

        http_response_code($code);

        header('Content-Type: application/json; charset=utf-8');
        print(json_encode($values));
        print("\n");
    }
}
