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

use App\Core\DB\DB;
use App\Core\Entity;
use App\Util\Common;
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
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Attachment extends Entity
{
    // {{{ Autocode
    private int $id;
    private ?string $remote_url;
    private ?string $remote_url_hash;
    private ?string $file_hash;
    private ?int $gsactor_id;
    private ?string $mimetype;
    private ?string $title;
    private ?string $filename;
    private ?bool $is_local;
    private ?int $source;
    private ?int $scope;
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

    public function setRemoteUrl(?string $remote_url): self
    {
        $this->remote_url = $remote_url;
        return $this;
    }

    public function getRemoteUrl(): ?string
    {
        return $this->remote_url;
    }

    public function setRemoteUrlHash(?string $remote_url_hash): self
    {
        $this->remote_url_hash = $remote_url_hash;
        return $this;
    }

    public function getRemoteUrlHash(): ?string
    {
        return $this->remote_url_hash;
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

    public function setGSActorId(?int $gsactor_id): self
    {
        $this->gsactor_id = $gsactor_id;
        return $this;
    }

    public function getGSActorId(): ?int
    {
        return $this->gsactor_id;
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

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
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

    public function setIsLocal(?bool $is_local): self
    {
        $this->is_local = $is_local;
        return $this;
    }

    public function getIsLocal(): ?bool
    {
        return $this->is_local;
    }

    public function setSource(?int $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getSource(): ?int
    {
        return $this->source;
    }

    public function setScope(?int $scope): self
    {
        $this->scope = $scope;
        return $this;
    }

    public function getScope(): ?int
    {
        return $this->scope;
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

    /**
     * Delete this file and by default all the associated entities (avatar and/or thumbnails, which this owns)
     */
    public function delete(bool $cascade = true, bool $flush = false, bool $delete_files_now = false): array
    {
        $files = [];
        if ($cascade) {
            // An avatar can own a file, and it becomes invalid if the file is deleted
            $avatar = DB::findBy('avatar', ['attachment_id' => $this->id]);
            foreach ($avatar as $a) {
                $files[] = $a->getFilePath();
                $a->delete($flush, $delete_files_now, $cascading = true);
            }
            foreach (DB::findBy('attachment_thumbnail', ['attachment_id' => $this->id]) as $ft) {
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

    public function getPath()
    {
        return Common::config('attachments', 'dir') . $this->getFilename();
    }

    public function getAttachmentUrl()
    {
        return Router::url('attachment_thumbnail', ['id' => $this->getAttachmentId(), 'w' => Common::config('attachment', 'width'), 'h' => Common::config('attachment', 'height')]);
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'attachment',
            'fields' => [
                'id'              => ['type' => 'serial',    'not null' => true],
                'remote_url'      => ['type' => 'text',      'description' => 'URL after following possible redirections'],
                'remote_url_hash' => ['type' => 'varchar',   'length' => 64,  'description' => 'sha256 of destination URL (url field)'],
                'file_hash'       => ['type' => 'varchar',   'length' => 64,  'description' => 'sha256 of the file contents, if the file is stored locally'],
                'gsactor_id'      => ['type' => 'int',       'foreign key' => true, 'target' => 'GSActor.id', 'multiplicity' => 'one to one', 'description' => 'If set, used so each actor can have a version of this file (for avatars, for instance)'],
                'mimetype'        => ['type' => 'varchar',   'length' => 50,  'description' => 'mime type of resource'],
                'title'           => ['type' => 'text',      'description' => 'title of resource when available'],
                'filename'        => ['type' => 'varchar',   'length' => 191, 'description' => 'title of resource when available'],
                'is_local'        => ['type' => 'bool',      'description' => 'whether the file is stored locally'],
                'source'          => ['type' => 'int',       'default' => null, 'description' => 'Source of the Attachment (upload, TFN, embed)'],
                'scope'           => ['type' => 'int',       'default' => null, 'description' => 'visibility scope for this attachment'],
                'modified'        => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                // 'file_file_key' => ['file_hash', 'actor_id'],
            ],
            'indexes' => [
                'file_filehash_idx' => ['file_hash'],
            ],
        ];
    }
}
