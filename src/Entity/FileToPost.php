<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/soci
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as publ
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public Li
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

namespace App\Entity;

/**
 * Entity for relating a file to a post
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
class FileToPost
{
    // {{{ Autocode

    private int $file_id;
    private int $post_id;
    private \DateTimeInterface $modified;

    public function setFileId(int $file_id): self
    {
        $this->file_id = $file_id;
        return $this;
    }
    public function getFileId(): int
    {
        return $this->file_id;
    }

    public function setPostId(int $post_id): self
    {
        $this->post_id = $post_id;
        return $this;
    }
    public function getPostId(): int
    {
        return $this->post_id;
    }

    public function setModified(\DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }
    public function getModified(): \DateTimeInterface
    {
        return $this->modified;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'file_to_post',
            'fields' => [
                'file_id'  => ['type' => 'int', 'not null' => true, 'description' => 'id of URL/file'],
                'post_id'  => ['type' => 'int', 'not null' => true, 'description' => 'id of the notice it belongs to'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['file_id', 'post_id'],
            'foreign keys' => [
                'file_to_post_file_id_fkey' => ['file', ['file_id' => 'id']],
                'file_to_post_post_id_fkey' => ['notice', ['post_id' => 'id']],
            ],
            'indexes' => [
                'file_id_idx' => ['file_id'],
                'post_id_idx' => ['post_id'],
            ],
        ];
    }
}