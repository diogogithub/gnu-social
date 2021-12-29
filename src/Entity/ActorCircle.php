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
use App\Core\Router\Router;
use DateTimeInterface;

/**
 * Entity for List of actors
 * This entity only makes sense when considered together with the ActorTag one.
 * Because, every circle entry will be an ActorTag.
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
    private ?int $tagger = null;
    private int $tagged;
    private string $tag;
    private bool $use_canonical;
    private ?string $description = null;
    private ?bool $private = false;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setTagger(?int $tagger): self
    {
        $this->tagger = $tagger;
        return $this;
    }

    public function getTagger(): ?int
    {
        return $this->tagger;
    }

    public function setTagged(int $tagged): self
    {
        $this->tagged = $tagged;
        return $this;
    }

    public function getTagged(): int
    {
        return $this->tagged;
    }

    public function setTag(string $tag): self
    {
        $this->tag = \mb_substr($tag, 0, 64);
        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setUseCanonical(bool $use_canonical): self
    {
        $this->use_canonical = $use_canonical;
        return $this;
    }

    public function getUseCanonical(): bool
    {
        return $this->use_canonical;
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

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    public function setModified(\DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): \DateTimeInterface
    {
        return $this->modified;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public function getActorTag()
    {
        return Cache::get(
            "actor-tag-{$this->getTag()}",
            fn () => DB::findBy('actor_tag', ['tagger' => $this->getTagger(), 'canonical' => $this->getTag()], limit: 1)[0], // TODO jank
        );
    }

    public function getSubscribedActors(?int $offset = null, ?int $limit = null): array
    {
        return Cache::get(
            "circle-{$this->getId()}",
            fn () => DB::dql(
                <<< 'EOQ'
                    SELECT a
                    FROM App\Entity\Actor a
                    JOIN App\Entity\ActorCircleSubscription s
                        WITH a.id = s.actor_id
                    ORDER BY s.created DESC, a.id DESC
                    EOQ,
                options: ['offset' => $offset,
                    'limit'        => $limit, ],
            ),
        );
    }

    public function getUrl(int $type = Router::ABSOLUTE_PATH): string {
        return Router::url('actor_circle', ['actor_id' => $this->getTagger(), 'tag' => $this->getTag()]);
    }

    public static function schemaDef(): array
    {
        return [
            'name'        => 'actor_circle',
            'description' => 'a actor can have lists of actors, to separate their feed',
            'fields'      => [
                'id'            => ['type' => 'serial',    'not null' => true, 'description' => 'unique identifier'], // An actor can be tagged by many actors
                'tagger'        => ['type' => 'int',       'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'many to one', 'name' => 'actor_list_tagger_fkey', 'description' => 'user making the tag'],
                'tagged'        => ['type' => 'int',       'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'name' => 'actor_tag_tagged_fkey', 'not null' => true, 'description' => 'actor tagged'],
                'tag'           => ['type' => 'varchar',   'length' => 64, 'foreign key' => true, 'target' => 'ActorTag.tag', 'multiplicity' => 'many to one', 'not null' => true, 'description' => 'actor tag'], // Join with ActorTag // // so, Doctrine doesn't like that the target is not unique, even though the pair is  // Many Actor Circles can reference (and probably will) an Actor Tag
                'use_canonical' => ['type' => 'bool',      'not null' => true, 'description' => 'whether the user wanted to block canonical tags'],
                'description'   => ['type' => 'text',      'description' => 'description of the people tag'],
                'private'       => ['type' => 'bool',      'default' => false, 'description' => 'is this tag private'],
                'created'       => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'indexes'     => [
                'actor_list_modified_idx'   => ['modified'],
                'actor_list_tag_idx'        => ['tag'],
                'actor_list_tagger_tag_idx' => ['tagger', 'tag'],
                'actor_list_tagger_idx'     => ['tagger'],
            ],
        ];
    }

    public function __toString()
    {
        return $this->getTag();
    }
}
