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
 * ActivityPub Keys System
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_rsa extends Managed_DataObject
{
    public $__table = 'activitypub_rsa';
    public $profile_id;                      // int(4)  primary_key not_null
    public $private_key;                     // text()   not_null
    public $public_key;                      // text()   not_null
    public $created;                         // datetime()   not_null default_CURRENT_TIMESTAMP
    public $modified;                        // datetime()   not_null default_CURRENT_TIMESTAMP

    /**
     * Return table definition for Schema setup and DB_DataObject usage.
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @return array array of column definitions
     */
    public static function schemaDef()
    {
        return [
                'fields' => [
                    'profile_id'  => ['type' => 'integer'],
                    'private_key' => ['type' => 'text'],
                    'public_key'  => ['type' => 'text', 'not null' => true],
                    'created' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                    'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
                ],
                'primary key' => ['profile_id'],
                'foreign keys' => [
                    'activitypub_profile_profile_id_fkey' => ['profile', ['profile_id' => 'id']],
                ],
        ];
    }

    public function get_private_key($profile)
    {
        $this->profile_id = $profile->getID();
        $apRSA = self::getKV('profile_id', $this->profile_id);
        if (!$apRSA instanceof Activitypub_rsa) {
            // No existing key pair for this profile
            if ($profile->isLocal()) {
                self::generate_keys($this->private_key, $this->public_key);
                $this->store_keys();
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
     * @param bool $fetch
     * @return string The public key
     * @throws ServerException It should never occur, but if so, we break everything!
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function ensure_public_key($profile, $fetch = true)
    {
        $this->profile_id = $profile->getID();
        $apRSA = self::getKV('profile_id', $this->profile_id);
        if (!$apRSA instanceof Activitypub_rsa) {
            // No existing key pair for this profile
            if ($profile->isLocal()) {
                self::generate_keys($this->private_key, $this->public_key);
                $this->store_keys();
            } else {
                // This should never happen, but try to recover!
                if ($fetch) {
                    $res = Activitypub_explorer::get_remote_user_activity(ActivityPubPlugin::actor_uri($profile));
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
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @access public
     * @throws ServerException
     */
    public function store_keys()
    {
        $this->created = $this->modified = common_sql_now();
        $ok = $this->insert();
        if ($ok === false) {
            throw new ServerException('Cannot save ActivityPub RSA.');
        }
    }

    /**
     * Generates a pair of RSA keys.
     *
     * @author PHP Manual Contributed Notes <dirt@awoms.com>
     * @param string $private_key in/out
     * @param string $public_key in/out
     */
    public static function generate_keys(&$private_key, &$public_key)
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
        $pubKey = openssl_pkey_get_details($res);
        $public_key = $pubKey["key"];
        unset($pubKey);
    }

    /**
     * Update public key.
     *
     * @param Profile $profile
     * @param string $public_key
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function update_public_key($profile, $public_key)
    {
        // Public Key
        $apRSA = new Activitypub_rsa();
        $apRSA->profile_id = $profile->getID();
        $apRSA->public_key = $public_key;
        $apRSA->modified = common_sql_now();
        if (!$apRSA->update()) {
            $apRSA->insert();
        }
    }
}
