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
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Router\Router;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\NotStoredLocallyException;
use App\Util\Exception\ServerException;
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
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class AttachmentThumbnail extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $attachment_id;
    private ?string $mimetype;
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

    public function setMimetype(?string $mimetype): self
    {
        $this->mimetype = $mimetype;
        return $this;
    }

    public function getMimetype(): ?string
    {
        return $this->mimetype;
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
     * @param int        $width
     * @param int        $height
     * @param bool       $crop
     *
     * @throws ClientException
     * @throws NotFoundException
     * @throws ServerException
     *
     * @return mixed
     */
    public static function getOrCreate(Attachment $attachment, int $width, int $height, bool $crop)
    {
        // We need to keep these in mind for DB indexing
        $predicted_width  = null;
        $predicted_height = null;
        try {
            if (is_null($attachment->getWidth()) || is_null($attachment->getHeight())) {
                // @codeCoverageIgnoreStart
                // TODO: check if we can generate from an existing thumbnail
                throw new ClientException(_m('Invalid dimensions requested for thumbnail.'));
                // @codeCoverageIgnoreEnd
            }
            return Cache::get('thumb-' . $attachment->getId() . "-{$width}x{$height}",
                function () use ($crop, $attachment, $width, $height, &$predicted_width, &$predicted_height) {
                    [$predicted_width, $predicted_height] = self::predictScalingValues($attachment->getWidth(), $attachment->getHeight(), $width, $height, $crop);
                    return DB::findOneBy('attachment_thumbnail', ['attachment_id' => $attachment->getId(), 'width' => $predicted_width, 'height' => $predicted_height]);
                });
        } catch (NotFoundException $e) {
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
            $encoders = array_merge($event_map[$mimetype], $event_map[$major_mime]);
            foreach ($encoders as $encoder) {
                $temp = null; // Let the EncoderPlugin create a temporary file for us
                if ($encoder($attachment->getPath(), $temp, $width, $height, $crop, $mimetype)) {
                    $thumbnail->setAttachment($attachment);
                    $thumbnail->setWidth($predicted_width);
                    $thumbnail->setHeight($predicted_height);
                    $ext      = '.' . MimeTypes::getDefault()->getExtensions($temp->getMimeType())[0];
                    $filename = "{$predicted_width}x{$predicted_height}{$ext}-" . $attachment->getFilehash();
                    $thumbnail->setFilename($filename);
                    $thumbnail->setMimetype($mimetype);
                    DB::persist($thumbnail);
                    DB::flush();
                    $temp->move(Common::config('thumbnail', 'dir'), $filename);
                    return $thumbnail;
                }
            }
            throw new ClientException(_m('Can not generate thumbnail for attachment with id={id}', ['id' => $attachment->getId()]));
        }
    }

    public function getPath()
    {
        return Common::config('thumbnail', 'dir') . DIRECTORY_SEPARATOR . $this->getFilename();
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
                // @codeCoverageIgnoreStart
                Log::warning("Failed deleting file for attachment thumbnail with id={$this->attachment_id}, width={$this->width}, height={$this->height} at {$filepath}");
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
     * @param $width    int Original width
     * @param $height   int Original height
     * @param $maxW     int Resulting max width
     * @param $maxH     int Resulting max height
     * @param $crop     bool Crop to the size (not preserving aspect ratio)
     *
     * @return array [predicted width, predicted height]
     */
    public static function predictScalingValues(
        int $existing_width,
        int $existing_height,
        int $requested_width,
        int $requested_height,
        bool $crop
    ): array {
        if ($crop) {
            $rw = min($existing_width, $requested_width);
            $rh = min($existing_height, $requested_height);
        } else {
            if ($existing_width > $existing_height) {
                $rw = min($existing_width, $requested_width);
                $rh = ceil($existing_height * $rw / $existing_width);
            } else {
                $rh = min($existing_height, $requested_height);
                $rw = ceil($existing_width * $rh / $existing_height);
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
                'mimetype'      => ['type' => 'varchar',   'length' => 129,  'description' => 'resource mime type 64+1+64, images hardly will show up with long mimetypes, this is probably safe considering rfc6838#section-4.2'],
                'width'         => ['type' => 'int', 'not null' => true, 'description' => 'width of thumbnail'],
                'height'        => ['type' => 'int', 'not null' => true, 'description' => 'height of thumbnail'],
                'filename'      => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'thumbnail filename'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['attachment_id', 'width', 'height'],
            'indexes'     => [
                'attachment_thumbnail_attachment_id_idx' => ['attachment_id'],
            ],
        ];
    }
}
