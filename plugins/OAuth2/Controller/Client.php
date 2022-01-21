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

namespace Plugin\OAuth2\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Log;
use Plugin\OAuth2\Entity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Client Management Endpoint
 *
 * @copyright 2022 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Client extends Controller
{
    public function onPost(Request $request): JsonResponse
    {
        Log::debug('OAuth2 Apps: Received a POST request.');
        Log::debug('OAuth2 Apps: Request content: ', [$body = $request->getContent()]);
        $args = json_decode($body, true);

        $identifier = hash('sha256', random_bytes(64)); // TODO maybe use password_hash and verify
        $secret     = hash('sha256', random_bytes(64));

        // TODO more validation
        $client = Entity\Client::create([
            'identifier'      => $identifier,
            'secret'          => $secret,
            'active'          => true,
            'plain_pcke'      => false,
            'is_confidential' => true,
            'redirect_uris'   => $args['redirect_uris'],
            'grants'          => 'client_credentials',
            'scopes'          => $args['scopes'] ?? 'read',
            'client_name'     => $args['client_name'],
            'website'         => $args['website'] ?? null,
        ]);
        DB::persist($client);
        DB::flush();

        Log::debug('OAuth2 Apps: Created App: ', [$client]);

        $app_response = [
            'id'            => (string) $client->getId(),
            'name'          => $client->getName(),
            'website'       => $client->getWebsite(),
            'redirect_uri'  => $client->getRedirectUri(),
            'client_id'     => $client->getIdentifier(),
            'client_secret' => $client->getSecret(),
        ];

        Log::debug('OAuth2 Apps: Create App response: ', [$app_response]);

        // Success
        return new JsonResponse($app_response, status: 200, headers: ['content_type' => 'application/json; charset=utf-8']);
    }
}
