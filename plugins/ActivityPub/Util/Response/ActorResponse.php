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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @category  ActivityPub
 *
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Util\Response;

use App\Entity\Actor as GSActor;
use App\Util\Exception\ClientException;
use Plugin\ActivityPub\Util\Model\Actor as ModelActor;
use Plugin\ActivityPub\Util\TypeResponse;

/**
 * Provides a response in application/ld+json to GSActors
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
abstract class ActorResponse
{
    /**
     * Provides a response in application/ld+json to GSActors
     *
     * @param int $status The response status code
     *
     * @throws ClientException
     */
    public static function handle(GSActor $gsactor, int $status = 200): TypeResponse
    {
        if ($gsactor->getIsLocal()) {
            return new TypeResponse(json: ModelActor::toJson($gsactor), status: $status);
        } else {
            throw new ClientException('This is a remote actor, you should request it to its source of authority instead.');
        }
    }
}
