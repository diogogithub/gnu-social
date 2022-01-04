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

namespace Component\Circle\Entity;

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
    private ?int $tagger = null; // If null, is the special global self-tag circle
    private string $tag;
    private ?string $description = null;
    private ?bool $private       = false;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

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

    public function setTag(string $tag): self
    {
        $this->tag = mb_substr($tag, 0, 64);
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

    /**
     * For use with MetaCollection trait only
     */
    public function getName(): string
    {
        return $this->tag;
    }

    public function getActorTags(bool $db_reference = false): array
    {
        $handle = fn () => DB::findBy('actor_tag', ['tagger' => $this->getTagger(), 'tag' => $this->getTag()]);
        if ($db_reference) {
            return $handle();
        }
        return Cache::get(
            "circle-{$this->getId()}-tagged",
            $handle,
        );
    }

    public function getTaggedActors()
    {
        return Cache::get(
            "circle-{$this->getId()}-tagged-actors",
            function () {
                if ($this->getTagger()) {
                    return DB::dql('SELECT a FROM actor AS a JOIN actor_tag AS at WITH at.tagged = a.id WHERE at.tag = :tag AND at.tagger = :tagger', ['tag' => $this->getTag(), 'tagger' => $this->getTagger()]);
                } else { // Self-tag
                    return DB::dql('SELECT a FROM actor AS a JOIN actor_tag AS at WITH at.tagged = a.id WHERE at.tag = :tag AND at.tagger = at.tagged', ['tag' => $this->getTag()]);
                }
            },
        );
    }

    public function getSubscribedActors(?int $offset = null, ?int $limit = null): array
    {
        return Cache::get(
            "circle-{$this->getId()}-subscribers",
            fn () => DB::dql(
                <<< 'EOQ'
                    SELECT a
                    FROM actor a
                    JOIN actor_circle_subscription s
                        WITH a.id = s.actor_id
                    ORDER BY s.created DESC, a.id DESC
                    EOQ,
                options: [
                    'offset' => $offset,
                    'limit'  => $limit,
                ],
            ),
        );
    }

    public function getUrl(int $type = Router::ABSOLUTE_PATH): string
    {
        return Router::url('actor_circle_view_by_circle_id', ['circle_id' => $this->getId()], type: $type);
    }

    public static function schemaDef(): array
    {
        return [
            'name'        => 'actor_circle',
            'description' => 'An actor can have lists of actors, to separate their feed or quickly mention his friend',
            'fields'      => [
                'id'          => ['type' => 'serial',    'not null' => true, 'description' => 'unique identifier'], // An actor can be tagged by many actors
                'tagger'      => ['type' => 'int',       'default' => null, 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'many to one', 'name' => 'actor_list_tagger_fkey', 'description' => 'user making the tag, null if self-tag'],
                'tag'         => ['type' => 'varchar',   'length' => 64, 'foreign key' => true, 'target' => 'ActorTag.tag', 'multiplicity' => 'many to one', 'not null' => true, 'description' => 'actor tag'], // Join with ActorTag // // so, Doctrine doesn't like that the target is not unique, even though the pair is  // Many Actor Circles can reference (and probably will) an Actor Tag
                'description' => ['type' => 'text',      'description' => 'description of the people tag'],
                'private'     => ['type' => 'bool',      'default' => false, 'description' => 'is this tag private'],
                'created'     => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'    => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'], // But we will mostly refer to them with `tagger` and `tag`
            'indexes'     => [
                'actor_list_modified_idx'   => ['modified'],
                'actor_list_tagger_tag_idx' => ['tagger', 'tag'], // The actual identifier we will use the most
                'actor_list_tag_idx'        => ['tag'],
                'actor_list_tagger_idx'     => ['tagger'],
            ],
        ];
    }
}
