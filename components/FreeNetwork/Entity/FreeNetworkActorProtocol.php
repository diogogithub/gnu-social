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
 *
 * @author    Diogo Peralta Cordeiro <@diogo.site
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Component\FreeNetwork\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use App\Entity\Actor;
use Component\FreeNetwork\Util\Discovery;
use DateTimeInterface;

/**
 * Table Definition for free_network_actor_protocol
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class FreeNetworkActorProtocol extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $actor_id;
    private ?string $protocol = null;
    private string $addr;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setActorId(int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getActorId(): int
    {
        return $this->actor_id;
    }

    public function setProtocol(?string $protocol): self
    {
        $this->protocol = \is_null($protocol) ? null : mb_substr($protocol, 0, 32);
        return $this;
    }

    public function getProtocol(): ?string
    {
        return $this->protocol;
    }

    public function setAddr(string $addr): self
    {
        $this->addr = $addr;
        return $this;
    }

    public function getAddr(): string
    {
        return $this->addr;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setModified(DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function protocolSucceeded(string $protocol, int|Actor $actor_id, string $addr): void
    {
        $actor_id            = \is_int($actor_id) ? $actor_id : $actor_id->getId();
        $attributed_protocol = self::getByPK(['actor_id' => $actor_id]);
        if (\is_null($attributed_protocol)) {
            $attributed_protocol = self::create([
                'actor_id' => $actor_id,
                'protocol' => $protocol,
                'addr'     => Discovery::normalize($addr),
            ]);
        } else {
            $attributed_protocol->setProtocol($protocol);
        }
        DB::wrapInTransaction(fn () => DB::persist($attributed_protocol));
    }

    public static function canIActor(string $protocol, int|Actor $actor_id): bool
    {
        $actor_id            = \is_int($actor_id) ? $actor_id : $actor_id->getId();
        $attributed_protocol = self::getByPK(['actor_id' => $actor_id])?->getProtocol();
        if (\is_null($attributed_protocol)) {
            // If it is not attributed, you can go ahead.
            return true;
        } else {
            // If it is attributed, you can on the condition that you're assigned to it.
            return $attributed_protocol === $protocol;
        }
    }

    public static function canIAddr(string $protocol, string $target): bool
    {
        // Normalize $addr, i.e. add 'acct:' if missing
        $addr                = Discovery::normalize($target);
        $attributed_protocol = self::getByPK(['addr' => $addr])?->getProtocol();
        if (\is_null($attributed_protocol)) {
            // If it is not attributed, you can go ahead.
            return true;
        } else {
            // If it is attributed, you can on the condition that you're assigned to it.
            return $attributed_protocol === $protocol;
        }
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'free_network_actor_protocol',
            'fields' => [
                'actor_id' => ['type' => 'int', 'not null' => true],
                'protocol' => ['type' => 'varchar',  'length' => 32, 'description' => 'the protocol plugin that should handle federation of this actor'],
                'addr'     => ['type' => 'text',  'not null' => true, 'description' => 'webfinger acct'],
                'created'  => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['actor_id'],
            'foreign keys' => [
                'activitypub_actor_actor_id_fkey' => ['actor', ['actor_id' => 'id']],
            ],
        ];
    }
}
