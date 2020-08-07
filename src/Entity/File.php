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

use App\Core\Entity;
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
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class File extends Entity
{
    // {{{ Autocode

    private int $id;
    private ?string $url;
    private ?bool $is_url_protected;
    private ?string $url_hash;
    private ?string $file_hash;
    private ?string $mimetype;
    private ?int $size;
    private ?string $title;
    private ?int $timestamp;
    private ?bool $is_local;
    private \DateTimeInterface $modified;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getId(): int
    {
        return $this->id;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }
    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setIsUrlProtected(?bool $is_url_protected): self
    {
        $this->is_url_protected = $is_url_protected;
        return $this;
    }
    public function getIsUrlProtected(): ?bool
    {
        return $this->is_url_protected;
    }

    public function setUrlHash(?string $url_hash): self
    {
        $this->url_hash = $url_hash;
        return $this;
    }
    public function getUrlHash(): ?string
    {
        return $this->url_hash;
    }

    public function setFileHash(?string $file_hash): self
    {
        $this->file_hash = $file_hash;
        return $this;
    }
    public function getFileHash(): ?string
    {
        return $this->file_hash;
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

    public function setSize(?int $size): self
    {
        $this->size = $size;
        return $this;
    }
    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTimestamp(?int $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }
    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    public function setIsLocal(?bool $is_local): self
    {
        $this->is_local = $is_local;
        return $this;
    }
    public function getIsLocal(): ?bool
    {
        return $this->is_local;
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

    const URLHASH_ALGO  = 'sha256';
    const FILEHASH_ALGO = 'sha256';

    public function getFileName(): string
    {
        return $this->file_hash;
    }

    /**
     * Delete this file and by default all the associated entities (avatar and/or thumbnails, which this owns)
     */
    public function delete(bool $cascade = true, bool $flush = false, bool $delete_files_now = false): array
    {
        $files = [];
        if ($cascade) {
            // An avatar can own a file, and it becomes invalid if the file is deleted
            $avatar  = DB::find('avatar', ['file_id' => $this->id]);
            $files[] = $avatar->getFilePath();
            $avatar->delete($flush, $delete_files_now, $cascading = true);
            foreach (DB::findBy('file_thumbnail', ['file_id' => $this->id]) as $ft) {
                $files[] = $ft->delete($flush, $delete_files_now, $cascading);
            }
        }
        DB::remove($this);
        if ($flush) {
            DB::flush();
        }
        if ($delete_files_now) {
            self::deleteFiles($files);
            return [];
        }
        return $files;
    }

    public static function deleteFiles(array $files)
    {
        foreach ($files as $f) {
            @unlink($f);
        }
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'file',
            'fields' => [
                'id'               => ['type' => 'serial',   'not null' => true],
                'url'              => ['type' => 'text',     'description' => 'URL after following possible redirections'],
                'is_url_protected' => ['type' => 'bool',     'default' => false, 'description' => 'true when URL is private (needs login)'],
                'url_hash'         => ['type' => 'varchar',  'length' => 64,  'description' => 'sha256 of destination URL (url field)'],
                'file_hash'        => ['type' => 'varchar',  'length' => 64,  'description' => 'sha256 of the file contents, if the file is stored locally'],
                'mimetype'         => ['type' => 'varchar',  'length' => 50,  'description' => 'mime type of resource'],
                'size'             => ['type' => 'int',      'description' => 'size of resource when available'],
                'title'            => ['type' => 'text',     'description' => 'title of resource when available'],
                'timestamp'        => ['type' => 'int',      'description' => 'unix timestamp according to http query'],
                'is_local'         => ['type' => 'bool',     'description' => 'whether the file is stored locally'],
                'modified'         => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'file_file_key' => ['file_hash'],
            ],
            'indexes' => [
                'file_filehash_idx' => ['file_hash'],
            ],
        ];
    }
}
