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
use App\Core\Router\Router;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\NotStoredLocallyException;
use App\Util\Exception\ServerException;
use App\Util\TemporaryFile;
use DateTimeInterface;
use Symfony\Component\Mime\MimeTypes;

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
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @author    Eliseu Amaro <mail@eliseuama.ro>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class AttachmentThumbnail extends Entity
{
    public const SIZE_SMALL  = 0;
    public const SIZE_MEDIUM = 1;
    public const SIZE_BIG    = 2;

    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $attachment_id;
    private ?string $mimetype;
    private int $size = self::SIZE_SMALL;
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

    public function setMimetype(?string $mimetype): self
    {
        $this->mimetype = $mimetype;
        return $this;
    }

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
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

    private ?Attachment $attachment = null;

    public function setAttachment(?Attachment $attachment)
    {
        $this->attachment = $attachment;
    }

    public function getAttachment()
    {
        if (isset($this->attachment) && !is_null($this->attachment)) {
            return $this->attachment;
        } else {
            return $this->attachment = DB::findOneBy('attachment', ['id' => $this->attachment_id]);
        }
    }

    /**
     * @param Attachment $attachment
     * @param ?string    $size
     * @param bool       $crop
     *
     * @throws ClientException
     * @throws NotFoundException
     * @throws ServerException
     *
     * @return mixed
     */
    public static function getOrCreate(Attachment $attachment, ?string $size = null, bool $crop = false)
    {
        $size     = $size ?? Common::config('thumbnail', 'default_size');
        $size_int = match ($size) {
            'medium' => self::SIZE_MEDIUM,
            'big'    => self::SIZE_BIG,
            default  => self::SIZE_SMALL,
        };
        try {
            return Cache::get('thumb-' . $attachment->getId() . "-{$size}",
                function () use ($attachment, $size_int) {
                    return DB::findOneBy('attachment_thumbnail', ['attachment_id' => $attachment->getId(), 'size' => $size_int]);
                });
        } catch (NotFoundException) {
            if (is_null($attachment->getWidth()) || is_null($attachment->getHeight())) {
                return null;
            }
            [$predicted_width, $predicted_height] = self::predictScalingValues($attachment->getWidth(), $attachment->getHeight(), $size, $crop);
            if (!file_exists($attachment->getPath())) {
                throw new NotStoredLocallyException();
            }
            $thumbnail              = self::create(['attachment_id' => $attachment->getId()]);
            $mimetype               = $attachment->getMimetype();
            $event_map[$mimetype]   = [];
            $major_mime             = GSFile::mimetypeMajor($mimetype);
            $event_map[$major_mime] = [];
            Event::handle('FileResizerAvailable', [&$event_map, $mimetype]);
            // Always prefer specific encoders
            /** @var callable[] function(string $source, ?TemporaryFile &$destination, int &$width, int &$height, bool $smart_crop, ?string &$mimetype): bool */
            $encoders = array_merge($event_map[$mimetype], $event_map[$major_mime]);
            foreach ($encoders as $encoder) {
                /** @var ?TemporaryFile */
                $temp = null; // Let the EncoderPlugin create a temporary file for us
                if ($encoder($attachment->getPath(), $temp, $predicted_width, $predicted_height, $crop, $mimetype)) {
                    $thumbnail->setAttachment($attachment);
                    $thumbnail->setSize($size_int);
                    $mimetype = $temp->getMimeType();
                    $ext      = '.' . MimeTypes::getDefault()->getExtensions($mimetype)[0];
                    $filename = "{$predicted_width}x{$predicted_height}{$ext}-" . $attachment->getFilehash();
                    $thumbnail->setFilename($filename);
                    $thumbnail->setMimetype($mimetype);
                    DB::persist($thumbnail);
                    DB::flush();
                    $temp->move(Common::config('thumbnail', 'dir'), $filename);
                    return $thumbnail;
                }
            }
            return null;
        }
    }

    public function getPath()
    {
        return Common::config('thumbnail', 'dir') . DIRECTORY_SEPARATOR . $this->getFilename();
    }

    public function getUrl()
    {
        return Router::url('attachment_thumbnail', ['id' => $this->getAttachmentId(), 'size' => $this->getSize()]);
    }

    /**
     * Delete an attachment thumbnail
     */
    public function delete(bool $flush = true): void
    {
        $filepath = $this->getPath();
        if (file_exists($filepath)) {
            if (@unlink($filepath) === false) {
                // @codeCoverageIgnoreStart
                Log::warning("Failed deleting file for attachment thumbnail with id={$this->getAttachmentId()}, size={$this->getSize()} at {$filepath}");
                // @codeCoverageIgnoreEnd
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
     * @param int    $existing_width  Original width
     * @param int    $existing_height Original height
     * @param string $requested_size
     * @param bool   $crop
     *
     * @return array [predicted width, predicted height]
     */
    public static function predictScalingValues(
        int $existing_width,
        int $existing_height,
        string $requested_size,
        bool $crop
    ): array {
        /**
         * 1:1   => Square
         * 4:3   => SD
         * 11:8  => Academy Ratio
         * 3:2   => Classic 35mm
         * 16:10 => Golden Ratio
         * 16:9  => Widescreen
         * 2.2:1 => Standard 70mm film
         */
        $allowed_aspect_ratios = [1, 1.3, 1.376, 1.5, 1.6, 1.7, 2.2]; // Ascending array
        $sizes                 = [
            'small'  => Common::config('thumbnail', 'small'),
            'medium' => Common::config('thumbnail', 'medium'),
            'big'    => Common::config('thumbnail', 'big'),
        ];

        // We only scale if the image is larger than the minimum width and height for a thumbnail
        if ($existing_width < Common::config('thumbnail', 'minimum_width') && $existing_height < Common::config('thumbnail', 'minimum_height')) {
            return [$existing_width, $existing_height];
        }

        // We only scale if the total of pixels is greater than the maximum allowed for a thumbnail
        $total_of_pixels = $existing_width * $existing_height;
        if ($total_of_pixels < Common::config('thumbnail', 'maximum_pixels')) {
            return [$existing_width, $existing_height];
        }

        // Is this a portrait image?
        $flip = $existing_height > $existing_width;

        // Find the aspect ratio of the given image
        $existing_aspect_ratio = !$flip ? $existing_width / $existing_height : $existing_height / $existing_width;

        // Binary search the closer allowed aspect ratio
        $left  = 0;
        $right = count($allowed_aspect_ratios) - 1;
        while ($left < $right) {
            $mid = floor($left + ($right - $left) / 2);

            // Comparing absolute distances with middle value and right value
            if (abs($existing_aspect_ratio - $allowed_aspect_ratios[$mid]) < abs($existing_aspect_ratio - $allowed_aspect_ratios[$right])) {
                // search the left side of the array
                $right = $mid;
            } else {
                // search the right side of the array
                $left = $mid + 1;
            }
        }
        $closest_aspect_ratio = $allowed_aspect_ratios[$left];
        unset($mid, $left, $right);

        // TODO: For crop, we should test a threshold and understand if the image would better be cropped

        // Resulting width and height
        $rw = (int) ($sizes[$requested_size]);
        $rh = (int) ($rw / $closest_aspect_ratio);

        return !$flip ? [$rw, $rh] : [$rh, $rw];
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'attachment_thumbnail',
            'fields' => [
                'attachment_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Attachment.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'thumbnail for what attachment'],
                'mimetype'      => ['type' => 'varchar',   'length' => 129,  'description' => 'resource mime type 64+1+64, images hardly will show up with long mimetypes, this is probably safe considering rfc6838#section-4.2'],
                'size'          => ['type' => 'int', 'not null' => true, 'default' => 0, 'description' => '0 = small; 1 = medium; 2 = big'],
                'filename'      => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'thumbnail filename'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['attachment_id', 'size'],
            'indexes'     => [
                'attachment_thumbnail_attachment_id_idx' => ['attachment_id'],
            ],
        ];
    }
}
