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

namespace App\Entity;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;
use DateTimeInterface;

/**
 * Entity for List of actors
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@hsal.es>
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActorCircle extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private int $tagger;
    private string $tag;
    private ?string $description;
    private ?bool $private;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setId(int $id): ActorCircle
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setTagger(int $tagger): self
    {
        $this->tagger = $tagger;
        return $this;
    }

    public function getTagger(): int
    {
        return $this->tagger;
    }

    public function setTag(string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setPrivate(?bool $private): self
    {
        $this->private = $private;
        return $this;
    }

    public function getPrivate(): ?bool
    {
        return $this->private;
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

    public function getSubscribedActors(?int $offset = null, ?int $limit = null): array
    {
        return Cache::get(
            "circle-{$this->getId()}",
            fn() => DB::dql(
                <<< EOQ
                    SELECT actor
                    FROM App\Entity\Actor actor
                    JOIN App\Entity\ActorCircleSubscription subscription
                        WITH actor.id = subscription.actor_id
                    ORDER BY subscription.created DESC, actor.id DESC
                    EOQ,
                options:
                ['offset' => $offset,
                    'limit' => $limit]
            )
        );
    }

    public static function schemaDef(): array
    {
        return [
            'name'        => 'actor_circle',
            'description' => 'a actor can have lists of actors, to separate their feed',
            'fields'      => [
                'id'          => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'tagger'      => ['type' => 'int',       'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'many to one', 'name' => 'actor_list_tagger_fkey', 'not null' => true, 'description' => 'user making the tag'],
                'tag'         => ['type' => 'varchar',   'length' => 64, 'foreign key' => true, 'target' => 'ActorTag.tag', 'multiplicity' => 'many to one', 'not null' => true, 'description' => 'actor tag'], // Join with ActorTag // // so, Doctrine doesn't like that the target is not unique, even though the pair is
                'description' => ['type' => 'text',      'description' => 'description of the people tag'],
                'private'     => ['type' => 'bool',      'default' => false, 'description' => 'is this tag private'],
                'created'     => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'    => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'indexes'     => [
                'actor_list_modified_idx'   => ['modified'],
                'actor_list_tag_idx'        => ['tag'],
                'actor_list_tagger_tag_idx' => ['tagger', 'tag'],
            ],
        ];
    }

    public function __toString()
    {
        return $this->getTag();
    }
}
