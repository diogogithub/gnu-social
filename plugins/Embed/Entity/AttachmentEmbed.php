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

/**
 * OembedPlugin implementation for GNU social
 *
 * @package   GNUsocial
 *
 * @author    Stephen Paul Weber
 * @author    Mikael Nordfeldth
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\Embed\Entity;

use App\Core\Entity;

/**
 * Table Definition for attachment_embed
 *
 * @author Hugo Sales <hugo@hsal.es>
 * @copyright 2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class AttachmentEmbed extends Entity
{
    // {{{ Autocode
    public $attachment_id;                         // int(4)  primary_key not_null
    public $version;                         // varchar(20)
    public $type;                            // varchar(20)
    public $mimetype;                        // varchar(50)
    public $provider;                        // varchar(50)
    public $provider_url;                    // varchar(191)   not 255 because utf8mb4 takes more space
    public $width;                           // int(4)
    public $height;                          // int(4)
    public $html;                            // text()
    public $title;                           // varchar(191)
    public $author_name;                     // varchar(50)
    public $author_url;                      // varchar(191)   not 255 because utf8mb4 takes more space
    public $url;                             // varchar(191)   not 255 because utf8mb4 takes more space
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP
    // }}} Autocode

    public static function schemaDef()
    {
        return [
            'name'   => 'attachment_embed',
            'fields' => [
                'attachment_id' => ['type' => 'int', 'not null' => true, 'description' => 'oEmbed for that URL/file'],
                'mimetype'      => ['type' => 'varchar', 'length' => 50, 'description' => 'mime type of resource'],
                'provider'      => ['type' => 'text', 'description' => 'name of this oEmbed provider'],
                'provider_url'  => ['type' => 'text', 'description' => 'URL of this oEmbed provider'],
                'width'         => ['type' => 'int', 'description' => 'width of oEmbed resource when available'],
                'height'        => ['type' => 'int', 'description' => 'height of oEmbed resource when available'],
                'html'          => ['type' => 'text', 'description' => 'html representation of this oEmbed resource when applicable'],
                'title'         => ['type' => 'text', 'description' => 'title of oEmbed resource when available'],
                'author_name'   => ['type' => 'text', 'description' => 'author name for this oEmbed resource'],
                'author_url'    => ['type' => 'text', 'description' => 'author URL for this oEmbed resource'],
                'url'           => ['type' => 'text', 'description' => 'URL for this oEmbed resource when applicable (photo, link)'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['attachment_id'],
            'foreign keys' => [
                'attachment_embed_attachment_id_fkey' => ['attachment', ['attachment_id' => 'id']],
            ],
        ];
    }
}
