<?php

declare(strict_types = 1);

// {{{ License/
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

namespace Component\Tag\Entity;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\Note;
use Component\Language\Entity\Language;
use Component\Tag\Tag;
use DateTimeInterface;

/**
 * Entity for Notice Tag
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
class NoteTag extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private string $tag;
    private string $canonical;
    private int $note_id;
    private bool $use_canonical;
    private ?int $language_id = null;
    private DateTimeInterface $created;

    public function setTag(string $tag): self
    {
        $this->tag = mb_substr($tag, 0, 64);
        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setCanonical(string $canonical): self
    {
        $this->canonical = mb_substr($canonical, 0, 64);
        return $this;
    }

    public function getCanonical(): string
    {
        return $this->canonical;
    }

    public function setNoteId(int $note_id): self
    {
        $this->note_id = $note_id;
        return $this;
    }

    public function getNoteId(): int
    {
        return $this->note_id;
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

    public function setLanguageId(?int $language_id): self
    {
        $this->language_id = $language_id;
        return $this;
    }

    public function getLanguageId(): ?int
    {
        return $this->language_id;
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

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function cacheKey(int|Note $note_id)
    {
        if (!\is_int($note_id)) {
            $note_id = $note_id->getId();
        }
        return "note-tags-{$note_id}";
    }

    public static function getByNoteId(int $note_id): array
    {
        return Cache::getList(self::cacheKey($note_id), fn () => DB::dql('SELECT nt FROM note_tag AS nt JOIN note AS n WITH n.id = nt.note_id WHERE n.id = :id', ['id' => $note_id]));
    }

    public function getUrl(?Actor $actor = null, int $type = Router::ABSOLUTE_PATH): string
    {
        $params['tag'] = $this->getTag();

        if (\is_null($this->getLanguageId())) {
            if (!\is_null($actor)) {
                $params['locale'] = $actor->getTopLanguage()->getLocale();
            }
        } else {
            $params['locale'] = Language::getById($this->getLanguageId())->getLocale();
        }
        if ($this->getUseCanonical()) {
            $params['canonical'] = $this->getCanonical();
        }

        return Router::url(id: 'single_note_tag', args: $params, type: $type);
    }

    public static function schemaDef(): array
    {
        return [
            'name'        => 'note_tag',
            'description' => 'Hash tags on notes',
            'fields'      => [
                'note_id'       => ['type' => 'int',      'foreign key' => true, 'target' => 'Note.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to tagged note'],
                'tag'           => ['type' => 'varchar',  'length' => Tag::MAX_TAG_LENGTH, 'not null' => true, 'description' => 'hash tag associated with this note'],
                'canonical'     => ['type' => 'varchar',  'length' => Tag::MAX_TAG_LENGTH, 'not null' => true, 'description' => 'ascii slug of tag'],
                'use_canonical' => ['type' => 'bool',     'not null' => true, 'description' => 'whether the user wanted to use canonical tags in this note. Separate for blocks'],
                'language_id'   => ['type' => 'int',      'not null' => false, 'foreign key' => true, 'target' => 'Language.id', 'multiplicity' => 'many to many', 'description' => 'the language this entry refers to'],
                'created'       => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['note_id', 'tag'], // No need to require language in this association because all the related tags will be in the note's language already
            'indexes'     => [
                'note_tag_created_idx'             => ['created'],
                'note_tag_note_id_idx'             => ['note_id'],
                'note_tag_tag_language_id_idx'     => ['tag', 'language_id'],
                'note_tag_tag_created_note_id_idx' => ['tag', 'created', 'note_id'],
            ],
        ];
    }
}
