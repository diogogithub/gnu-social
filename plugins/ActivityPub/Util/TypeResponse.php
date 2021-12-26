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

namespace Plugin\ActivityPub\Util;

use ActivityPhp\Type\AbstractObject;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * TypeResponse represents an HTTP response in application/ld+json format.
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class TypeResponse extends JsonResponse
{
    /**
     * Provides a response in application/ld+json for ActivityStreams 2.0 Types
     *
     * @param null|AbstractObject|string $json
     * @param int                        $status The response status code
     */
    public function __construct(string|AbstractObject|null $json = null, int $status = 202)
    {
        parent::__construct(
            data: \is_object($json) ? $json->toJson() : $json,
            status: $status,
            headers: ['content-type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"'],
            json: true,
        );
    }
}
