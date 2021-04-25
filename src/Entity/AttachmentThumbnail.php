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

namespace App\Entity;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Event;
use App\Core\GSFile;
use App\Core\Log;
use App\Core\Router;
use App\Util\Common;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\ServerException;
use DateTimeInterface;

/**
 * Entity for Attachment thumbnails
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class AttachmentThumbnail extends Entity
{
    // {{{ Autocode
    private int $attachment_id;
    private int $width;
    private int $height;
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

    public function setWidth(int $width): self
    {
        $this->width = $width;
        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;
        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
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

    // }}} Autocode

    private Attachment $attachment;

    public function setAttachment(Attachment $attachment)
    {
        $this->attachment = $attachment;
    }

    public function getAttachment()
    {
        if (isset($this->attachment)) {
            return $this->attachment;
        } else {
            return $this->attachment = DB::findOneBy('attachment', ['id' => $this->attachment_id]);
        }
    }

    public static function getOrCreate(Attachment $attachment, ?int $width = null, ?int $height = null, ?bool $crop = null)
    {
        try {
            return Cache::get('thumb-' . $attachment->getId() . "-{$width}x{$height}",
                              function () use ($attachment, $width, $height) {
                                  return DB::findOneBy('attachment_thumbnail', ['attachment_id' => $attachment->getId(), 'width' => $width, 'height' => $height]);
                              });
        } catch (NotFoundException $e) {
            $thumbnail  = self::create(['attachment_id' => $attachment->getId(), 'width' => $width, 'height' => $height, 'attachment' => $attachment]);
            $event_map  = ['image' => 'ResizeImage', 'video' => 'ResizeVideo'];
            $major_mime = GSFile::mimetypeMajor($attachment->getMimetype());
            if (in_array($major_mime, array_keys($event_map))) {
                Event::handle($event_map[$major_mime], [$attachment, $thumbnail, $width, $height, $crop]);
                DB::persist($thumbnail);
                DB::flush();
                return $thumbnail;
            } else {
                Log::debug($m = ('Cannot resize attachment with mimetype ' . $attachment->getMimetype()));
                throw new ServerException($m);
            }
        }
    }

    public function getFilename()
    {
        return $this->getAttachment()->getFileHash() . "-{$this->width}x{$this->height}.webp";
    }

    public function getPath()
    {
        return Common::config('thumbnail', 'dir') . $this->getFilename();
    }

    public function getUrl()
    {
        return Router::url('attachment_thumbnail', ['id' => $this->getAttachmentId(), 'w' => $this->getWidth(), 'h' => $this->getHeight()]);
    }

    /**
     * Get the HTML attributes for this thumbnail
     */
    public function getHTMLAttributes(array $orig = [], bool $overwrite = true)
    {
        $attrs = [
            'height' => $this->getHeight(),
            'width'  => $this->getWidth(),
            'src'    => $this->getUrl(),
        ];
        return $overwrite ? array_merge($orig, $attrs) : array_merge($attrs, $orig);
    }

    /**
     * Delete a attachment thumbnail. This table doesn't own all the attachments, only itself
     */
    public function delete(bool $flush = false, bool $delete_attachments_now = false, bool $cascading = false): string
    {
        // TODO Implement deleting attachment thumbnails
        return '';
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'attachment_thumbnail',
            'fields' => [
                'attachment_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Attachment.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'thumbnail for what attachment'],
                'width'         => ['type' => 'int', 'not null' => true, 'description' => 'width of thumbnail'],
                'height'        => ['type' => 'int', 'not null' => true, 'description' => 'height of thumbnail'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['attachment_id', 'width', 'height'],
            'indexes'     => [
                'attachment_thumbnail_attachment_id_idx' => ['attachment_id'],
            ],
        ];
    }
}
