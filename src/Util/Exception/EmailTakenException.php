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

/**
 * Email taken exception
 *
 * @category  Exception
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util\Exception;

use function App\Core\I18n\_m;
use App\Entity\Actor;

class EmailTakenException extends EmailException
{
    public ?Actor $profile = null;    // the Actor which occupies the email

    public function __construct(?Actor $profile = null, ?string $msg = null, int $code = 400)
    {
        $this->profile = $profile;
        parent::__construct($msg, $code);
    }

    protected function defaultMessage(): string
    {
        // TRANS: Validation error in form for registration, profile and group settings, etc.
        return _m('Email is already in use on this server.');
    }
}
