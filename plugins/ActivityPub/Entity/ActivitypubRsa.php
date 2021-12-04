<?php

declare(strict_types=1);

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
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Log;
use App\Entity\Actor;
use App\Util\Exception\ServerException;
use DateTimeInterface;

/**
 * ActivityPub Keys System
 *
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActivitypubRsa extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $actor_id;
    private ?string $private_key = null;
    private string $public_key;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function getActorId(): int
    {
        return $this->actor_id;
    }

    public function setActorId(int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getPrivateKey(): string
    {
        return $this->private_key;
    }

    public function setPrivateKey(string $private_key): self
    {
        $this->private_key = $private_key;
        return $this;
    }

    public function getPublicKey(): string
    {
        return $this->public_key;
    }

    public function setPublicKey(string $public_key): self
    {
        $this->public_key = $public_key;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    public function setModified(DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }
    // @codeCoverageIgnoreEnd
    // }}} Autocode

    /**
     * Return table definition for Schema setup and Entity usage.
     *
     * @return array array of column definitions
     */
    public static function schemaDef(): array
    {
        return [
            'name' => 'activitypub_rsa',
            'fields' => [
                'actor_id' => ['type' => 'int', 'not null' => true],
                'private_key' => ['type' => 'text'],
                'public_key' => ['type' => 'text', 'not null' => true],
                'created' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['actor_id'],
            'foreign keys' => [
                'activitypub_rsa_actor_id_fkey' => ['actor', ['actor_id' => 'id']],
            ],
        ];
    }

    /**
     * Guarantees RSA keys for a given actor.
     *
     * @param Actor $gsactor
     * @param bool $fetch =true Should attempt to fetch keys from a remote profile?
     * @return ActivitypubRsa The keys (private key is null for remote actors)
     * @throws ServerException It should never occur, but if so, we break everything!
     */
    public static function getByActor(Actor $gsactor, bool $fetch = true): self
    {
        $apRSA = self::getWithPK(['actor_id' => ($actor_id = $gsactor->getId())]);
        if (is_null($apRSA)) {
            // Nonexistent key pair for this profile
            if ($gsactor->getIsLocal()) {
                self::generateKeys($private_key, $public_key);
                $apRSA = self::create([
                    'actor_id' => $actor_id,
                    'private_key' => $private_key,
                    'public_key' => $public_key,
                ]);
                DB::wrapInTransaction(fn() => DB::persist($apRSA));
            } else {
                // ASSERT: This should never happen, but try to recover!
                Log::error("Activitypub_rsa: An impossible thing has happened... Please let the devs know.");
                if ($fetch) {
                    //$res = Activitypub_explorer::get_remote_user_activity($profile->getUri());
                    //Activitypub_rsa::update_public_key($profile, $res['publicKey']['publicKeyPem']);
                    //return self::ensure_public_key($profile, false);
                } else {
                    throw new ServerException('Activitypub_rsa: Failed to find keys for given profile. That should have not happened!');
                }
            }
        }
        return $apRSA;
    }

    /**
     * Generates a pair of RSA keys.
     *
     * @param string|null $private_key out
     * @param string|null $public_key out
     * @author PHP Manual Contributed Notes <dirt@awoms.com>
     */
    private static function generateKeys(?string &$private_key, ?string &$public_key): void
    {
        $config = [
            'digest_alg' => 'sha512',
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
}
