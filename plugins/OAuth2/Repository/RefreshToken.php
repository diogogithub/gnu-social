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
 * OAuth2 implementation for GNU social
 *
 * @package   OAuth2
 * @category  API
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2022 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\OAuth2\Repository;

use App\Core\DB\DB;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Plugin\OAuth2\Entity;

class RefreshToken implements RefreshTokenRepositoryInterface
{
    public function persistNewRefreshToken(RefreshtokenEntityInterface $refreshtokenEntity)
    {
        DB::persist($refreshtokenEntity);
    }

    public function revokeRefreshToken($tokenId)
    {
        // Some logic to revoke the auth token in a database
    }

    public function isRefreshtokenRevoked($tokenId)
    {
        return false; // The auth token has not been revoked
    }

    public function getNewRefreshToken()
    {
        return new Entity\RefreshToken();
    }
}
