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
 * @package   OAuth2
 * @category  API
 *
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\IndieAuth\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Log;
use App\Util\Common;
use Plugin\IndieAuth\Entity\OAuth2Client;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * App Management Endpoint
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Apps extends Controller
{
    public function onPost(): JsonResponse
    {
        Log::debug('OAuth2 Apps: Received a POST request.');
        Log::debug('OAuth2 Apps: Request content: ', [$body = $this->request->getContent()]);
        $args = json_decode($body, true);

        $identifier = hash('md5', random_bytes(16));
        // Random string Length should be between 43 and 128
        $secret = Common::base64url_encode(hash('sha256', random_bytes(57)));

        DB::persist($app = OAuth2Client::create([
            'identifier'            => $identifier,
            'secret'                => $secret,
            'redirect_uris'         => $args['redirect_uris'],
            'grants'                => 'client_credentials authorization_code',
            'scopes'                => $args['scopes'],
            'active'                => true,
            'allow_plain_text_pkce' => false,
            'client_name'           => $args['client_name'],
            'website'               => $args['website'],
        ]));

        Log::debug('OAuth2 Apps: Created App: ', [$app]);

        DB::flush();

        // Success
        return new JsonResponse([
            'name'          => $app->getClientName(),
            'website'       => $app->getWebsite(),
            'redirect_uri'  => $app->getRedirectUris()[0],
            'client_id'     => $app->getIdentifier(),
            'client_secret' => $app->getSecret(),
        ], status: 200, headers: ['content_type' => 'application/json; charset=utf-8']);
    }
}
