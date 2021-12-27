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

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Event;
use App\Core\GSFile;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Router\Router;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\ServerException;
use DateTimeInterface;

/**
 * Entity for uploaded files
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
class Attachment extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private int $lives        = 1;
    private ?string $filehash = null;
    private ?string $mimetype = null;
    private ?string $filename = null;
    private ?int $size        = null;
    private ?int $width       = null;
    private ?int $height      = null;
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

    public function setLives(int $lives): self
    {
        $this->lives = $lives;
        return $this;
    }

    public function getLives(): int
    {
        return $this->lives;
    }

    public function setFilehash(?string $filehash): self
    {
        $this->filehash = \is_null($filehash) ? null : mb_substr($filehash, 0, 64);
        return $this;
    }

    public function getFilehash(): ?string
    {
        return $this->filehash;
    }

    public function setMimetype(?string $mimetype): self
    {
        $this->mimetype = \is_null($mimetype) ? null : mb_substr($mimetype, 0, 255);
        return $this;
    }

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = \is_null($filename) ? null : mb_substr($filename, 0, 191);
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setWidth(?int $width): self
    {
        $this->width = $width;
        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setHeight(?int $height): self
    {
        $this->height = $height;
        return $this;
    }

    public function getHeight(): ?int
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

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public function getMimetypeMajor(): ?string
    {
        $mime = $this->getMimetype();
        return \is_null($mime) ? $mime : GSFile::mimetypeMajor($mime);
    }

    public function getMimetypeMinor(): ?string
    {
        $mime = $this->getMimetype();
        return \is_null($mime) ? $mime : GSFile::mimetypeMinor($mime);
    }

    public function livesIncrementAndGet(): int
    {
        ++$this->lives;
        return $this->lives;
    }

    public function livesDecrementAndGet(): int
    {
        --$this->lives;
        return $this->lives;
    }

    public const FILEHASH_ALGO = 'sha256';

    /**
     * Delete a file if safe, removes dependencies, cleanups and flushes
     */
    public function kill(): bool
    {
        if ($this->livesDecrementAndGet() <= 0) {
            return $this->delete();
        }
        return true;
    }

    /**
     * Remove the respective file from disk
     */
    public function deleteStorage(): bool
    {
        if (!\is_null($filepath = $this->getPath())) {
            if (file_exists($filepath)) {
                if (@unlink($filepath) === false) {
                    // @codeCoverageIgnoreStart
                    Log::error("Failed deleting file for attachment with id={$this->getId()} at {$filepath}.");
                    return false;
                // @codeCoverageIgnoreEnd
                } else {
                    $this->setFilename(null);
                    $this->setSize(null);
                    // Important not to null neither width nor height
                    DB::persist($this);
                    DB::flush();
                }
            } else {
                // @codeCoverageIgnoreStart
                Log::warning("File for attachment with id={$this->getId()} at {$filepath} was already deleted when I was going to handle it.");
                // @codeCoverageIgnoreEnd
            }
        }
        return true;
    }

    /**
     * Attachment delete always removes dependencies, cleanups and flushes
     *
     * @see kill() It's more likely that you want to use that rather than call delete directly
     */
    protected function delete(): bool
    {
        // Friendly warning because the caller usually doesn't want to delete an attachment that is still referred elsewhere
        if ($this->getLives() > 0) {
            // @codeCoverageIgnoreStart
            Log::warning("Deleting file {$this->getId()} with {$this->getLives()} lives. Why are you killing it so old?");
            // @codeCoverageIgnoreEnd
        }

        // Collect files starting with the one associated with this attachment
        $files = [];
        if (!\is_null($filepath = $this->getPath())) {
            $files[] = $filepath;
        }

        // Collect thumbnail files and delete thumbnails
        foreach ($this->getThumbnails() as $at) {
            $files[] = $at->getPath();
            $at->delete(flush: false);
        }

        // Delete eventual remaining relations with Actors
        ActorToAttachment::removeWhereAttachmentId($this->getId());

        // Delete eventual remaining relations with Notes
        AttachmentToNote::removeWhereAttachmentId($this->getId());

        // Delete eventual remaining relations with Links
        AttachmentToLink::removeWhereAttachmentId($this->getId());

        // Remove this attachment
        DB::remove($this);

        // Delete the files from disk
        foreach ($files as $f) {
            if (file_exists($f)) {
                if (@unlink($f) === false) {
                    // @codeCoverageIgnoreStart
                    Log::error("Failed deleting file for attachment with id={$this->getId()} at {$f}.");
                    // @codeCoverageIgnoreEnd
                }
            } else {
                // @codeCoverageIgnoreStart
                Log::warning("File for attachment with id={$this->getId()} at {$f} was already deleted when I was going to handle it.");
                // @codeCoverageIgnoreEnd
            }
        }

        // Flush these changes as we have deleted the files from disk
        DB::flush();
        return true;
    }

    /**
     * TODO: Maybe this isn't the best way of handling titles
     *
     * @throws DuplicateFoundException
     * @throws NotFoundException
     * @throws ServerException
     */
    public function getBestTitle(?Note $note = null): string
    {
        // If we have a note, then the best title is the title itself
        if (!\is_null(($note))) {
            $title = Cache::get('attachment-title-' . $this->getId() . '-' . $note->getId(), function () use ($note) {
                try {
                    $attachment_to_note = DB::findOneBy('attachment_to_note', [
                        'attachment_id' => $this->getId(),
                        'note_id'       => $note->getId(),
                    ]);
                    if (!\is_null($attachment_to_note->getTitle())) {
                        return $attachment_to_note->getTitle();
                    }
                } catch (NotFoundException) {
                    $title = null;
                    Event::handle('AttachmentGetBestTitle', [$this, $note, &$title]);
                    return $title;
                }
            });
            if ($title != null) {
                return $title;
            }
        }
        // Else
        if (!\is_null($filename = $this->getFilename())) {
            // A filename would do just as well
            return $filename;
        } else {
            // Welp
            return _m('Untitled attachment');
        }
    }

    /**
     * Find all thumbnails associated with this attachment. Don't bother caching as this is not supposed to be a common operation
     */
    public function getThumbnails()
    {
        return DB::findBy('attachment_thumbnail', ['attachment_id' => $this->id]);
    }

    public function getPath()
    {
        $filename = $this->getFilename();
        return \is_null($filename) ? null : Common::config('attachments', 'dir') . \DIRECTORY_SEPARATOR . $filename;
    }

    public function getUrl(Note|int $note, int $type = Router::ABSOLUTE_URL): string
    {
        return Router::url(id: 'note_attachment_view', args: ['note_id' => \is_int($note) ? $note : $note->getId(), 'attachment_id' => $this->getId()], type: $type);
    }

    public function getShowUrl(Note|int $note, int $type = Router::ABSOLUTE_URL): string
    {
        return Router::url(id: 'note_attachment_show', args: ['note_id' => \is_int($note) ? $note : $note->getId(), 'attachment_id' => $this->getId()], type: $type);
    }

    public function getDownloadUrl(Note|int $note, int $type = Router::ABSOLUTE_URL): string
    {
        return Router::url(id: 'note_attachment_download', args: ['note_id' => \is_int($note) ? $note : $note->getId(), 'attachment_id' => $this->getId()], type: $type);
    }

    /**
     * @throws ClientException
     * @throws NotFoundException
     * @throws ServerException
     *
     * @return AttachmentThumbnail
     */
    public function getThumbnail(?string $size = null, bool $crop = false): ?AttachmentThumbnail
    {
        return AttachmentThumbnail::getOrCreate(attachment: $this, size: $size, crop: $crop);
    }

    public function getThumbnailUrl(Note|int $note, ?string $size = null)
    {
        return Router::url('note_attachment_thumbnail', ['note_id' => \is_int($note) ? $note : $note->getId(), 'attachment_id' => $this->getId(), 'size' => $size ?? Common::config('thumbnail', 'default_size')]);
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'attachment',
            'fields' => [
                'id'       => ['type' => 'serial',    'not null' => true],
                'lives'    => ['type' => 'int',       'default' => 1, 'not null' => true, 'description' => 'RefCount, starts with 1'],
                'filehash' => ['type' => 'varchar',   'length' => 64,  'description' => 'sha256 of the file contents, if the file is stored locally'],
                'mimetype' => ['type' => 'varchar',   'length' => 255,  'description' => 'resource mime type 127+1+127 as per rfc6838#section-4.2'],
                'filename' => ['type' => 'varchar',   'length' => 191, 'description' => 'file name of resource when available'],
                'size'     => ['type' => 'int',       'description' => 'size of resource when available'],
                'width'    => ['type' => 'int',       'description' => 'width in pixels, if it can be described as such and data is available'],
                'height'   => ['type' => 'int',       'description' => 'height in pixels, if it can be described as such and data is available'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'attachment_filehash_uniq' => ['filehash'],
                'attachment_filename_uniq' => ['filename'],
            ],
            'indexes' => [
                'file_filehash_idx' => ['filehash'],
            ],
        ];
    }
}
