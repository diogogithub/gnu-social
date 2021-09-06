<?php

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

namespace Plugin\Cover\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use App\Entity\Attachment;
use App\Util\Common;
use DateTimeInterface;

/**
 * For storing a cover
 *
 * @package  GNUsocial
 * @category CoverPlugin
 *
 * @author    Daniel Brandao <up201705812@fe.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Cover extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $gsactor_id;
    private int $attachment_id;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

    public function setGSActorId(int $gsactor_id): self
    {
        $this->gsactor_id = $gsactor_id;
        return $this;
    }

    public function getGSActorId(): int
    {
        return $this->gsactor_id;
    }

    public function setAttachmentId(int $attachment_id): self
    {
        $this->attachment_id = $attachment_id;
        return $this;
    }

    public function getAttachmentId(): int
    {
        return $this->attachment_id;
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

    private ?Attachment $attachment = null;

    /**
     * get cover attachment
     *
     * @return Attachment
     */
    public function getAttachment(): Attachment
    {
        $this->attachment = $this->attachment ?: DB::find('attachment', ['id' => $this->attachment_id]);
        return $this->attachment;
    }

    /**
     * get cover attachment path
     *
     * @return string
     */
    public function getAttachmentPath(): string
    {
        return Common::config('cover', 'dir') . $this->getAttachment()->getBestTitle();
    }

    /**
     * Delete this cover and the corresponding attachment and thumbnails, which this owns
     *
     * @param bool $flush
     * @param bool $delete_attachments_now
     * @param bool $cascading
     *
     * @return array attachments deleted (if delete_attachments_now is true)
     */
    public function delete(bool $flush = false, bool $delete_attachments_now = false, bool $cascading = false): array
    {
        // Don't go into a loop if we're deleting from Attachment
        if (!$cascading) {
            $attachments = $this->getAttachment()->kill();
        } else {
            DB::remove(DB::getReference('cover', ['gsactor_id' => $this->gsactor_id]));
            $attachment_path = $this->getAttachmentPath();
            $attachments[]   = $attachment_path;
            if ($flush) {
                DB::flush();
            }
            return $delete_attachments_now ? [] : $attachments;
        }
        return [];
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'cover',
            'fields' => [
                'gsactor_id'    => ['type' => 'int',       'foreign key' => true, 'target' => 'GSActor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to gsactor table'],
                'attachment_id' => ['type' => 'int',       'foreign key' => true, 'target' => 'Attachment.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to attachment table'],
                'created'       => ['type' => 'datetime',  'not null' => true, 'description' => 'date this record was created',  'default' => 'CURRENT_TIMESTAMP'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified', 'default' => 'CURRENT_TIMESTAMP'],
            ],
            'primary key' => ['gsactor_id'],
            'indexes'     => [
                'cover_attachment_id_idx' => ['attachment_id'],
            ],
        ];
    }
}
