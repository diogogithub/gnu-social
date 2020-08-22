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
 * ActivityPub Assymetric Key Storage System
 *
 * @package   GNUsocial
 *
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Entity;

class ActivityPubCryptKey
{
    // {{{ Autocode
    // }}} Autocode

    /**
     * Private key getter
     *
     * @param Profile $profile
     *
     * @throws Exception Throws exception if tries to fetch a private key of an actor we don't own
     *
     * @return string The private key
     */
    public function get_private_key(Profile $profile): string
    {
        $this->profile_id = $profile->getID();
        $apRSA            = self::getKV('profile_id', $this->profile_id);
        if (!$apRSA instanceof Activitypub_rsa) {
            // Nonexistent key pair for this profile
            if ($profile->isLocal()) {
                self::generate_keys($this->private_key, $this->public_key);
                $this->store_keys();
                $apRSA->private_key = $this->private_key;
            } else {
                throw new Exception('This is a remote Profile, there is no Private Key for this Profile.');
            }
        }
        return $apRSA->private_key;
    }

    /**
     * Guarantees a Public Key for a given profile.
     *
     * @param Profile $profile
     * @param bool    $fetch=true Should attempt to fetch keys from a remote profile?
     *
     * @throws ServerException It should never occur, but if so, we break everything!
     * @throws Exception
     *
     * @return string The public key
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function ensure_public_key(Profile $profile, bool $fetch = true): string
    {
        $this->profile_id = $profile->getID();
        $apRSA            = self::getKV('profile_id', $this->profile_id);
        if (!$apRSA instanceof Activitypub_rsa) {
            // No existing key pair for this profile
            if ($profile->isLocal()) {
                self::generate_keys($this->private_key, $this->public_key);
                $this->store_keys();
                $apRSA->public_key = $this->public_key;
            } else {
                // ASSERT: This should never happen, but try to recover!
                common_log(LOG_ERR, 'Activitypub_rsa: An impossible thing has happened... Please let the devs know that it entered in line 116 at Activitypub_rsa.php');
                if ($fetch) {
                    $res = Activitypub_explorer::get_remote_user_activity($profile->getUri());
                    Activitypub_rsa::update_public_key($profile, $res['publicKey']['publicKeyPem']);
                    return self::ensure_public_key($profile, false);
                } else {
                    throw new ServerException('Activitypub_rsa: Failed to find keys for given profile. That should have not happened!');
                }
            }
        }
        return $apRSA->public_key;
    }

    /**
     * Insert the current object variables into the database.
     *
     * @throws ServerException
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function store_keys(): void
    {
        $this->created = $this->modified = common_sql_now();
        $ok            = $this->insert();
        if ($ok === false) {
            throw new ServerException('Cannot save ActivityPub RSA.');
        }
    }

    /**
     * Generates a pair of RSA keys.
     *
     * @param string $private_key out
     * @param string $public_key  out
     *
     * @author PHP Manual Contributed Notes <dirt@awoms.com>
     */
    public static function generate_keys(?string &$private_key, ?string &$public_key): void
    {
        $config = [
            'digest_alg'       => 'sha512',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the private and public key
        $res = openssl_pkey_new($config);

        // Extract the private key from $res to $private_key
        openssl_pkey_export($res, $private_key);

        // Extract the public key from $res to $pubKey
        $pubKey     = openssl_pkey_get_details($res);
        $public_key = $pubKey['key'];
        unset($pubKey);
    }

    /**
     * Update public key.
     *
     * @param Activitypub_profile|Profile $profile
     * @param string                      $public_key
     *
     * @throws Exception
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function update_public_key($profile, string $public_key): void
    {
        // Public Key
        $apRSA             = new Activitypub_rsa();
        $apRSA->profile_id = $profile->getID();
        $apRSA->public_key = $public_key;
        $apRSA->created    = common_sql_now();
        if (!$apRSA->update()) {
            $apRSA->insert();
        }
    }

    public static function schemaDef()
    {
        return [
            'name'        => 'activitypub_crypt_key',
            'description' => 'assymetric key storage for activitypub',
            'fields'      => [
                'gsactor_id'  => ['type' => 'int', 'not null' => true],
                'private_key' => ['type' => 'text'],
                'public_key'  => ['type' => 'text', 'not null' => true],
                'created'     => ['type' => 'datetime', 'description' => 'date this record was created'],
                'modified'    => ['type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['gsactor_id'],
            'foreign keys' => [
                'activitypub_rsa_gsactor_id_fkey' => ['gsactor', ['gsactor_id' => 'id']],
            ],
        ];
    }
}
