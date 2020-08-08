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
use App\Core\Router\Router;
use App\Util\Common;
use DateTimeInterface;

/**
 * Entity for user's avatar
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
class Avatar extends Entity
{
    // {{{ Autocode

    private int $profile_id;
    private int $file_id;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

    public function setProfileId(int $profile_id): self
    {
        $this->profile_id = $profile_id;
        return $this;
    }

    public function getProfileId(): int
    {
        return $this->profile_id;
    }

    public function setFileId(int $file_id): self
    {
        $this->file_id = $file_id;
        return $this;
    }

    public function getFileId(): int
    {
        return $this->file_id;
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

    // }}} Autocode

    private ?File $file = null;

    public function getUrl(): string
    {
        return Router::url('avatar', ['nickname' => Profile::getNicknameFromId($this->profile_id)]);
    }

    public function getFile(): File
    {
        $this->file = $this->file ?: DB::find('file', ['id' => $this->file_id]);
        return $this->file;
    }

    public function getFilePath(?string $filename = null): string
    {
        return Common::config('avatar', 'dir') . '/' . $filename ?: $this->getFile()->getFileName();
    }

    /**
     * Delete this avatar and the corresponding file and thumbnails, which this owns
     */
    public function delete(bool $flush = false, bool $delete_files_now = false, bool $cascading = false): array
    {
        // Don't go into a loop if we're deleting from File
        if (!$cascading) {
            $files = $this->getFile()->delete($cascade = true, $file_flush = false, $delete_files_now);
        } else {
            DB::remove(DB::getReference('avatar', ['profile_id' => $this->profile_id]));
            $file_path = $this->getFilePath();
            $files[]   = $file_path;
            if ($flush) {
                DB::flush();
            }
            return $delete_files_now ? [] : $files;
        }
        return [];
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'avatar',
            'fields' => [
                'profile_id' => ['type' => 'int',       'not null' => true, 'description' => 'foreign key to profile table'],
                'file_id'    => ['type' => 'int',       'not null' => true, 'description' => 'foreign key to file table'],
                'created'    => ['type' => 'datetime',  'not null' => true, 'description' => 'date this record was created',  'default' => 'CURRENT_TIMESTAMP'],
                'modified'   => ['type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified', 'default' => 'CURRENT_TIMESTAMP'],
            ],
            'primary key'  => ['profile_id'],
            'foreign keys' => [
                'avatar_profile_id_fkey' => ['profile', ['profile_id' => 'id']],
                'avatar_file_id_fkey'    => ['file', ['file_id' => 'id']],
            ],
            'indexes' => [
                'avatar_file_id_idx' => ['file_id'],
            ],
        ];
    }
}
