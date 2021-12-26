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

namespace Component\Link\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Event;
use DateTimeInterface;

/**
 * Entity for relating a Link to a post
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class NoteToLink extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $link_id;
    private int $note_id;
    private DateTimeInterface $modified;

    public function setLinkId(int $link_id): self
    {
        $this->link_id = $link_id;
        return $this;
    }

    public function getLinkId(): int
    {
        return $this->link_id;
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
     * Create an instance of NoteToLink or fill in the
     * properties of $obj with the associative array $args. Doesn't
     * persist the result
     *
     * @param null|mixed $obj
     */
    public static function create(array $args, $obj = null)
    {
        $link = DB::find('link', ['id' => $args['link_id']]);
        $note = DB::find('note', ['id' => $args['note_id']]);
        Event::handle('NewLinkFromNote', [$link, $note]);
        $obj = new self();
        return parent::create($args, $obj);
    }

    public static function removeWhereNoteId(int $note_id): mixed
    {
        return DB::dql(
            <<<'EOF'
                DELETE FROM note_to_link ntl
                WHERE ntl.note_id = :note_id
                EOF,
            ['note_id' => $note_id],
        );
    }

    public static function removeWhere(int $link_id, int $note_id): mixed
    {
        return DB::dql(
            <<<'EOF'
                DELETE FROM note_to_link ntl
                WHERE (ntl.link_id = :link_id
                           OR ntl.note_id = :note_id)
                EOF,
            ['link_id' => $link_id, 'note_id' => $note_id],
        );
    }

    public static function removeWhereLinkId(int $link_id): mixed
    {
        return DB::dql(
            <<<'EOF'
                DELETE FROM note_to_link ntl
                WHERE ntl.link_id = :link_id
                EOF,
            ['link_id' => $link_id],
        );
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'note_to_link',
            'fields' => [
                'link_id'  => ['type' => 'int', 'foreign key' => true, 'target' => 'link.id', 'multiplicity' => 'one to one', 'name' => 'note_to_link_link_id_fkey', 'not null' => true, 'description' => 'id of link'],
                'note_id'  => ['type' => 'int', 'foreign key' => true, 'target' => 'Note.id', 'multiplicity' => 'one to one', 'name' => 'note_to_link_note_id_fkey', 'not null' => true, 'description' => 'id of the note it belongs to'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['link_id', 'note_id'],
            'indexes'     => [
                'link_id_idx' => ['link_id'],
                'note_id_idx' => ['note_id'],
            ],
        ];
    }
}
