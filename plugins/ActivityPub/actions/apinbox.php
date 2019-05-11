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

defined('GNUSOCIAL') || die();

/**
 * Inbox Request Handler
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class apInboxAction extends ManagedAction
{
    protected $needLogin = false;
    protected $canPost   = true;

    /**
     * Handle the Inbox request
     *
     * @return void
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    protected function handle()
    {
        $path = !empty($this->trimmed('id')) ? common_local_url('apInbox', ['id' => $this->trimmed('id')]) : common_local_url('apInbox');
        $path = parse_url($path, PHP_URL_PATH);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ActivityPubReturn::error('Only POST requests allowed.');
        }

        common_debug('ActivityPub Inbox: Received a POST request.');
        $body = $data = file_get_contents('php://input');
        common_debug('ActivityPub Inbox: Request contents: '.$data);
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['actor'])) {
            ActivityPubReturn::error('Actor not found in the request.');
        }

        $actor = Activitypub_explorer::get_profile_from_url($data['actor']);
        $aprofile = Activitypub_profile::from_profile($actor);

        $actor_public_key = new Activitypub_rsa();
        $actor_public_key = $actor_public_key->ensure_public_key($actor);

        common_debug('ActivityPub Inbox: HTTP Signature: Validation will now start!');

        $headers = $this->get_all_headers();
        common_debug('ActivityPub Inbox: Request Headers: '.print_r($headers, true));

        if (!isset($headers['signature'])) {
            common_debug('ActivityPub Inbox: HTTP Signature: Missing Signature header.');
            ActivityPubReturn::error('Missing Signature header.', 400);
        }

        // Extract the signature properties
        $signatureData = HTTPSignature::parseSignatureHeader($headers['signature']);
        common_debug('ActivityPub Inbox: HTTP Signature Data: '.print_r($signatureData, true));
        if (isset($signatureData['error'])) {
            common_debug('ActivityPub Inbox: HTTP Signature: '.json_encode($signatureData, true));
            ActivityPubReturn::error(json_encode($signatureData, true), 400);
        }

        list($verified, $headers) = HTTPSignature::verify($actor_public_key, $signatureData, $headers, $path, $body);

        // If the signature fails verification the first time, update profile as it might have change public key
        if($verified !== 1) {
            $res = Activitypub_explorer::get_remote_user_activity($aprofile->getUri());
            $actor = Activitypub_profile::update_profile($aprofile, $res);
            $actor_public_key = new Activitypub_rsa();
            $actor_public_key = $actor_public_key->ensure_public_key($actor);
            list($verified, $headers) = HTTPSignature::verify($actor_public_key, $signatureData, $headers, $path, $body);
        }

        // If it still failed despite profile update
        if($verified !== 1) {
            common_debug('ActivityPub Inbox: HTTP Signature: Invalid signature.');
            ActivityPubReturn::error('Invalid signature.');
        }

        // HTTP signature checked out, make sure the "actor" of the activity matches that of the signature
        common_debug('ActivityPub Inbox: HTTP Signature: Authorized request. Will now start the inbox handler.');

        try {
            new Activitypub_inbox_handler($data, $actor);
            ActivityPubReturn::answer();
        } catch (Exception $e) {
            ActivityPubReturn::error($e->getMessage());
        }
    }

    /**
     * Get all HTTP header key/values as an associative array for the current request.
     *
     * @return array [string] The HTTP header key/value pairs.
     * @author PHP Manual Contributed Notes <joyview@gmail.com>
     */
    private function get_all_headers()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[strtolower(str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))))] = $value;
            }
        }
        return $headers;
    }
}
