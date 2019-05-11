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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 * @link      http://www.gnu.org/software/social/
 */

namespace Tests;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return todo
     */
    public static function createApplication()
    {
        if (!defined('INSTALLDIR')) {
            define('INSTALLDIR', __DIR__ . '/../../../');
        }
        if (!defined('GNUSOCIAL')) {
            define('GNUSOCIAL', true);
        }
        if (!defined('STATUSNET')) {
            define('STATUSNET', true);  // compatibility
        }

        require INSTALLDIR . '/lib/common.php';

        return true;
    }
}
