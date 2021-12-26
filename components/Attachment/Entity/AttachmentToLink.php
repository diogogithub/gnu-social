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

namespace Component\Attachment\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use DateTimeInterface;

/**
 * Entity for relating a remote url to an attachment
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class AttachmentToLink extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $attachment_id;
    private int $link_id;
    private DateTimeInterface $modified;

    public function setAttachmentId(int $attachment_id): self
    {
        $this->attachment_id = $attachment_id;
        return $this;
    }

    public function getAttachmentId(): int
    {
        return $this->attachment_id;
    }

    public function setLinkId(int $link_id): self
    {
        $this->link_id = $link_id;
        return $this;
    }

    public function getLinkId(): int
    {
        return $this->link_id;
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

    public static function removeWhereAttachmentId(int $attachment_id): mixed
    {
        return DB::dql(
            <<<'EOF'
                DELETE FROM attachment_to_link atl
                WHERE atl.attachment_id = :attachment_id
                EOF,
            ['attachment_id' => $attachment_id],
        );
    }

    public static function removeWhere(int $link_id, int $attachment_id): mixed
    {
        return DB::dql(
            <<<'EOF'
                DELETE FROM attachment_to_link atl
                WHERE (atl.link_id = :link_id
                           OR atl.attachment_id = :attachment_id)
                EOF,
            ['link_id' => $link_id, 'attachment_id' => $attachment_id],
        );
    }

    public static function removeWhereLinkId(int $link_id): mixed
    {
        return DB::dql(
            <<<'EOF'
                DELETE FROM attachment_to_link atl
                WHERE atl.link_id = :link_id
                EOF,
            ['link_id' => $link_id],
        );
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'attachment_to_link',
            'fields' => [
                'link_id'       => ['type' => 'int', 'foreign key' => true, 'target' => 'Link.id', 'multiplicity' => 'one to one', 'name' => 'attachment_to_note_note_id_fkey', 'not null' => true, 'description' => 'id of the note it belongs to'],
                'attachment_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Attachment.id', 'multiplicity' => 'one to one', 'name' => 'attachment_to_note_attachment_id_fkey', 'not null' => true, 'description' => 'id of attachment'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['link_id'],
            'indexes'     => [
                'link_id_idx'       => ['link_id'],
                'attachment_id_idx' => ['attachment_id'],
            ],
        ];
    }
}
