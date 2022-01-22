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
 * OAuth2 Client
 *
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2022 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\OAuth2\Entity;

use App\Core\Entity;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

abstract class AccessToken extends Entity implements AccessTokenEntityInterface
{
    // {{{ Autocode
    // }}} Autocode

    public function setPrivateKey(CryptKey $privateKey)
    {
    }

    public function __toString()
    {
    }

    private int $id;

    public static function schemaDef(): array
    {
        return [
            'name'   => 'oauth2_access_token',
            'fields' => [
                'id' => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
            ],
            'primary key' => ['id'],
        ];
    }
}
