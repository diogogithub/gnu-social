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
 * Entity for File thumbnails
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
class FileThumbnail
{
    // {{{ Autocode

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'file_thumbnail',
            'fields' => [
                'file_id'  => ['type' => 'int', 'not null' => true, 'description' => 'thumbnail for what URL/file'],
                'urlhash'  => ['type' => 'varchar', 'length' => 64, 'description' => 'sha256 of url field if non-empty'],
                'url'      => ['type' => 'text', 'description' => 'URL of thumbnail'],
                'filename' => ['type' => 'text', 'description' => 'if stored locally, filename is put here'],
                'width'    => ['type' => 'int', 'not null' => true, 'description' => 'width of thumbnail'],
                'height'   => ['type' => 'int', 'not null' => true, 'description' => 'height of thumbnail'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['file_id', 'width', 'height'],
            'indexes'     => [
                'file_thumbnail_file_id_idx' => ['file_id'],
                'file_thumbnail_urlhash_idx' => ['urlhash'],
            ],
            'foreign keys' => [
                'file_thumbnail_file_id_fkey' => ['file', ['file_id' => 'id']],
            ],
        ];
    }
}