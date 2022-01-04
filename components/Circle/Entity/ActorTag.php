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

use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Router\Router;
use App\Entity\Actor;
use Component\Tag\Tag;
use DateTimeInterface;

/**
 * Entity for Actor Tag
 * This entity represents the relationship between an Actor and a Tag.
 * That relationship works as follows:
 * An Actor A tags an Actor B (which can be A - a self tag).
 * For every tagging that happens between two actors, a new ActorTag is born.
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
class ActorTag extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $tagger;
    private int $tagged;
    private string $tag;
    private DateTimeInterface $modified;

    public function setTagger(int $tagger): self
    {
        $this->tagger = $tagger;
        return $this;
    }

    public function getTagger(): int
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
        $this->tag = mb_substr($tag, 0, 64);
        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
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

    public function getUrl(?Actor $actor = null, int $type = Router::ABSOLUTE_PATH): string
    {
        $params = ['tag' => $this->getTag()];
        if (!\is_null($actor)) {
            $params['locale'] = $actor->getTopLanguage()->getLocale();
        }
        return Router::url('single_actor_tag', $params, type: $type);
    }

    public function getCircle(): ActorCircle
    {
        if ($this->getTagger() === $this->getTagged()) { // Self-tag
            return DB::findOneBy(ActorCircle::class, ['tagger' => null, 'tag' => $this->getTag()]);
        } else {
            return DB::findOneBy(ActorCircle::class, ['tagger' => $this->getTagger(), 'tag' => $this->getTag()]);
        }
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'actor_tag',
            'fields' => [
                'tagger'   => ['type' => 'int',       'not null' => true, 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'name' => 'actor_tag_tagger_fkey', 'description' => 'actor making the tag'],
                'tagged'   => ['type' => 'int',       'not null' => true, 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'name' => 'actor_tag_tagged_fkey', 'description' => 'actor tagged'],
                'tag'      => ['type' => 'varchar',  'length' => Tag::MAX_TAG_LENGTH, 'not null' => true, 'description' => 'hashtag associated with this note'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ], // We will always assume the tagger's preferred language for tags and circles
            'primary key' => ['tagger', 'tagged', 'tag'],
            'indexes'     => [
                'actor_tag_tagger_tag_idx' => ['tagger', 'tag'], // For Circles
                'actor_tag_tagged_idx'     => ['tagged'],
            ],
        ];
    }
}
