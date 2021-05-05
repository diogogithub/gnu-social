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
use App\Util\Common;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\ServerException;
use App\Util\TemporaryFile;
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
    // @codeCoverageIgnoreStart
    private int $attachment_id;
    private int $width;
    private int $height;
    private string $filename;
    private \DateTimeInterface $modified;

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

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
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

    public static function getOrCreate(Attachment $attachment, int $width, int $height, bool $crop)
    {
        try {
            return Cache::get('thumb-' . $attachment->getId() . "-{$width}x{$height}",
                function () use ($crop, $attachment, $width, $height) {
                    [$predicted_width, $predicted_height] = self::predictScalingValues($attachment->getWidth(),$attachment->getHeight(), $width, $height, $crop);
                    return DB::findOneBy('attachment_thumbnail', ['attachment_id' => $attachment->getId(), 'width' => $predicted_width, 'height' => $predicted_height]);
                });
        } catch (NotFoundException $e) {
            $ext        = image_type_to_extension(IMAGETYPE_WEBP, include_dot: true);
            $temp       = new TemporaryFile(['prefix' => 'thumbnail', 'suffix' => $ext]);
            $thumbnail  = self::create(['attachment_id' => $attachment->getId()]);
            $event_map  = ['image' => 'ResizeImagePath', 'video' => 'ResizeVideoPath'];
            $major_mime = GSFile::mimetypeMajor($attachment->getMimetype());
            if (in_array($major_mime, array_keys($event_map))) {
                Event::handle($event_map[$major_mime], [$attachment->getPath(), $temp->getRealPath(), &$width, &$height, $crop, &$mimetype]);
                $thumbnail->setWidth($width);
                $thumbnail->setHeight($height);
                $filename = "{$width}x{$height}{$ext}-" . $attachment->getFileHash();
                $temp->commit(Common::config('thumbnail', 'dir') . $filename);
                $thumbnail->setFilename($filename);
                DB::persist($thumbnail);
                DB::flush();
                return $thumbnail;
            } else {
                Log::debug($m = ('Cannot resize attachment with mimetype ' . $attachment->getMimetype()));
                throw new ServerException($m);
            }
        }
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
     * Delete an attachment thumbnail
     */
    public function delete(bool $flush = true): void
    {
        $filepath = $this->getPath();
        if (file_exists($filepath)) {
            if (@unlink($filepath) === false) {
                Log::warning("Failed deleting file for attachment thumbnail with id={$this->attachment_id}, width={$this->width}, height={$this->height} at {$filepath}");
            }
        }
        DB::remove($this);
        if ($flush) {
            DB::flush();
        }
    }

    /**
     * Gets scaling values for images of various types. Cropping can be enabled.
     *
     * Values will scale _up_ to fit max values if cropping is enabled!
     * With cropping disabled, the max value of each axis will be respected.
     *
     * @param $width    int Original width
     * @param $height   int Original height
     * @param $maxW     int Resulting max width
     * @param $maxH     int Resulting max height
     * @param $crop     bool Crop to the size (not preserving aspect ratio)
     *
     * @return array [predicted width, predicted height]
     */
    public static function predictScalingValues(
        int $width,
        int $height,
        int $maxW,
        int $maxH,
        bool $crop
    ): array {
        // Cropping data (for original image size). Default values, 0 and null,
        // imply no cropping and with preserved aspect ratio (per axis).
        $cx = 0;    // crop x
        $cy = 0;    // crop y
        $cw = null; // crop area width
        $ch = null; // crop area height

        if ($crop) {
            $s_ar = $width / $height;
            $t_ar = $maxW  / $maxH;

            $rw = $maxW;
            $rh = $maxH;

            // Source aspect ratio differs from target, recalculate crop points!
            if ($s_ar > $t_ar) {
                $cx = floor($width / 2 - $height * $t_ar / 2);
                $cw = ceil($height * $t_ar);
            } elseif ($s_ar < $t_ar) {
                $cy = floor($height / 2 - $width / $t_ar / 2);
                $ch = ceil($width / $t_ar);
            }
        } else {
            $rw = $maxW;
            $rh = ceil($height * $rw / $width);

            // Scaling caused too large height, decrease to max accepted value
            if ($rh > $maxH) {
                $rh = $maxH;
                $rw = ceil($width * $rh / $height);
            }
        }
        return [(int) $rw, (int) $rh];
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'attachment_thumbnail',
            'fields' => [
                'attachment_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Attachment.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'thumbnail for what attachment'],
                'width'         => ['type' => 'int', 'not null' => true, 'description' => 'width of thumbnail'],
                'height'        => ['type' => 'int', 'not null' => true, 'description' => 'height of thumbnail'],
                'filename'      => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'thubmnail filename'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['attachment_id', 'width', 'height'],
            'indexes'     => [
                'attachment_thumbnail_attachment_id_idx' => ['attachment_id'],
            ],
        ];
    }
}
