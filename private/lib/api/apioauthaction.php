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
 * Base action for OAuth API endpoints
 *
 * @category  API
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

require_once INSTALLDIR . '/lib/api/apiaction.php';

/**
 * Base action for API OAuth enpoints. Clean up the
 * request. Some other common functions.
 *
 * @category  API
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ApiOAuthAction extends ApiAction
{
    /**
     * Is this a read-only action?
     *
     * @return boolean false
     */
    public function isReadOnly($args)
    {
        return false;
    }

    protected function prepare(array $args=array())
    {
        self::cleanRequest();
        return parent::prepare($args);
    }

    /*
     * Clean up the request so the OAuth library doesn't find
     * any extra parameters or anything else it's not expecting.
     * I'm looking at you, p parameter.
     */

    public static function cleanRequest()
    {
        // strip out the p param added in index.php
        unset($_GET['p']);
        unset($_POST['p']);
        unset($_REQUEST['p']);

        $queryArray = explode('&', $_SERVER['QUERY_STRING']);

        for ($i = 0; $i < sizeof($queryArray); $i++) {
            if (substr($queryArray[$i], 0, 2) == 'p=') {
                unset($queryArray[$i]);
            }
        }

        $_SERVER['QUERY_STRING'] = implode('&', $queryArray);
    }
}
