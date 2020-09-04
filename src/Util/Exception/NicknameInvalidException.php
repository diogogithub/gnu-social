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

/**
 * Nickname invalid exception
 *
 * @category  Exception
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Brion Vibber <brion@pobox.com>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Nym Coy <nymcoy@gmail.com>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @auuthor   Daniel Supernault <danielsupernault@gmail.com>
 * @auuthor   Diogo Cordeiro <diogo@fc.up.pt>
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2018-2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util\Exception;

use function App\Core\I18n\_m;

class NicknameInvalidException extends NicknameException
{
    protected function defaultMessage(): string
    {
        // TRANS: Validation error in form for registration, profile and group settings, etc.
        return _m('Nickname must have only lowercase letters and numbers and no spaces.');
    }
}
