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

namespace Plugin\OAuth2\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Log;
use App\Util\Common;
use Plugin\OAuth2\Entity\OAuth2ClientMeta;
use Symfony\Component\HttpFoundation\JsonResponse;
use Trikoder\Bundle\OAuth2Bundle\Model\Client;
use Trikoder\Bundle\OAuth2Bundle\Model\Grant;
use Trikoder\Bundle\OAuth2Bundle\Model\RedirectUri;
use Trikoder\Bundle\OAuth2Bundle\Model\Scope;

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

        $identifier = hash('md5', random_bytes(16)); // Random string Length should be between 43 and 128
        $secret     = Common::base64url_encode(hash('sha256', random_bytes(57)));

        $client = new Client($identifier, $secret);
        $client->setActive(true);
        $client->setAllowPlainTextPkce(false);

        $redirectUris = array_map(
            static fn (string $redirectUri): RedirectUri => new RedirectUri($redirectUri),
            explode(' ', $args['redirect_uris']),
        );
        $client->setRedirectUris(...$redirectUris);

        $client->setGrants(new Grant('client_credentials'));

        $scopes = array_map(
            static fn (string $scope): Scope => new Scope($scope),
            explode(' ', $args['scopes']),
        );
        $client->setScopes(...$scopes);

        DB::persist($client);

        DB::persist($additional_meta = OAuth2ClientMeta::create([
            'identifier'  => $client->getIdentifier(),
            'client_name' => $args['client_name'],
            'website'     => $args['website'],
        ]));

        Log::debug('OAuth2 Apps: Created App: ', [$client, $additional_meta]);
        $app_meta = [
            'id'            => (string) $additional_meta->getId(),
            'name'          => $additional_meta->getClientName(),
            'website'       => $additional_meta->getWebsite(),
            'redirect_uri'  => (string) $client->getRedirectUris()[0],
            'client_id'     => $client->getIdentifier(),
            'client_secret' => $client->getSecret(),
        ];

        Log::debug('OAuth2 Apps: Create App Meta: ', [$app_meta]);

        DB::flush();

        // Success
        return new JsonResponse($app_meta, status: 200, headers: ['content_type' => 'application/json; charset=utf-8']);
    }
}
