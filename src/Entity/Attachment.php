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

use App\Core\Router\Router;
use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\GSFile;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Util\Common;
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
    private int $lives = 1;
    private ?string $filehash;
    private ?string $mimetype;
    private ?string $filename;
    private ?int $size;
    private ?int $width;
    private ?int $height;
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

    /**
     * @return int
     */
    public function getLives(): int
    {
        return $this->lives;
    }

    /**
     * @param int $lives
     */
    public function setLives(int $lives): void
    {
        $this->lives = $lives;
    }

    public function setFilehash(?string $filehash): self
    {
        $this->filehash = $filehash;
        return $this;
    }

    public function getFilehash(): ?string
    {
        return $this->filehash;
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

    public function getMimetypeMajor(): ?string
    {
        $mime = $this->getMimetype();
        return is_null($mime) ? $mime : GSFile::mimetypeMajor($mime);
    }

    public function getMimetypeMinor(): ?string
    {
        $mime = $this->getMimetype();
        return is_null($mime) ? $mime : GSFile::mimetypeMinor($mime);
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;
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

    /**
     * @return int
     */
    public function livesIncrementAndGet(): int
    {
        ++$this->lives;
        return $this->lives;
    }

    /**
     * @return int
     */
    public function livesDecrementAndGet(): int
    {
        --$this->lives;
        return $this->lives;
    }

    const FILEHASH_ALGO = 'sha256';

    /**
     * Delete a file if safe, removes dependencies, cleanups and flushes
     *
     * @return bool
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
        if (!is_null($filepath = $this->getPath())) {
            if (file_exists($filepath)) {
                if (@unlink($filepath) === false) {
                    Log::error("Failed deleting file for attachment with id={$this->getId()} at {$filepath}.");
                    return false;
                } else {
                    $this->setFilename(null);
                    $this->setSize(null);
                    // Important not to null neither width nor height
                    DB::persist($this);
                    DB::flush();
                }
            } else {
                Log::warning("File for attachment with id={$this->getId()} at {$filepath} was already deleted when I was going to handle it.");
            }
        }
        return true;
    }

    /**
     * Attachment delete always removes dependencies, cleanups and flushes
     */
    protected function delete(): bool
    {
        if ($this->getLives() > 0) {
            Log::warning("Deleting file {$this->getId()} with {$this->getLives()} lives. Why are you killing it so young?");
        }
        // Delete related files from storage
        $files = [];
        if (!is_null($filepath = $this->getPath())) {
            $files[] = $filepath;
        }
        foreach ($this->getThumbnails() as $at) {
            $files[] = $at->getPath();
            $at->delete(flush: false);
        }
        DB::remove($this);
        foreach ($files as $f) {
            if (file_exists($f)) {
                if (@unlink($f) === false) {
                    Log::error("Failed deleting file for attachment with id={$this->getId()} at {$f}.");
                }
            } else {
                Log::warning("File for attachment with id={$this->getId()} at {$f} was already deleted when I was going to handle it.");
            }
        }
        DB::flush();
        return true;
    }

    /**
     * TODO: Maybe this isn't the best way of handling titles
     *
     * @param null|Note $note
     *
     * @throws DuplicateFoundException
     * @throws NotFoundException
     * @throws ServerException
     *
     * @return string
     */
    public function getBestTitle(?Note $note = null): string
    {
        // If we have a note, then the best title is the title itself
        if (!is_null(($note))) {
            $attachment_to_note = DB::findOneBy('attachment_to_note', [
                'attachment_id' => $this->getId(),
                'note_id'       => $note->getId(),
            ]);
            if (!is_null($attachment_to_note->getTitle())) {
                return $attachment_to_note->getTitle();
            }
        }
        // Else
        if (!is_null($filename = $this->getFilename())) {
            // A filename would do just as well
            return $filename;
        } else {
            // Welp
            return _m('Untitled Attachment.');
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
        return is_null($filename) ? null : Common::config('attachments', 'dir') . $filename;
    }

    public function getUrl()
    {
        return Router::url('attachment_view', ['id' => $this->getId()]);
    }

    public function getThumbnailUrl()
    {
        return Router::url('attachment_thumbnail', ['id' => $this->getId(), 'w' => Common::config('thumbnail', 'width'), 'h' => Common::config('thumbnail', 'height')]);;
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'attachment',
            'fields' => [
                'id'       => ['type' => 'serial',    'not null' => true],
                'lives'    => ['type' => 'int',       'not null' => true, 'description' => 'RefCount'],
                'filehash' => ['type' => 'varchar',   'length' => 64,  'description' => 'sha256 of the file contents, if the file is stored locally'],
                'mimetype' => ['type' => 'varchar',   'length' => 64,  'description' => 'mime type of resource'],
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
