<?php
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

/**
 * OembedPlugin implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Stephen Paul Weber
 * @author    Mikael Nordfeldth
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Table Definition for file_embed
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class File_embed extends Managed_DataObject
{
    public $__table = 'file_embed';                     // table name
    public $file_id;                         // int(4)  primary_key not_null
    public $version;                         // varchar(20)
    public $type;                            // varchar(20)
    public $mimetype;                        // varchar(50)
    public $provider;                        // varchar(50)
    public $provider_url;                    // varchar(191)   not 255 because utf8mb4 takes more space
    public $width;                           // int(4)
    public $height;                          // int(4)
    public $html;                            // text()
    public $title;                           // varchar(191)   not 255 because utf8mb4 takes more space
    public $author_name;                     // varchar(50)
    public $author_url;                      // varchar(191)   not 255 because utf8mb4 takes more space
    public $url;                             // varchar(191)   not 255 because utf8mb4 takes more space
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'file_id' => array('type' => 'int', 'not null' => true, 'description' => 'oEmbed for that URL/file'),
                'version' => array('type' => 'varchar', 'length' => 20, 'description' => 'oEmbed spec. version'),
                'type' => array('type' => 'varchar', 'length' => 20, 'description' => 'oEmbed type: photo, video, link, rich'),
                'mimetype' => array('type' => 'varchar', 'length' => 50, 'description' => 'mime type of resource'),
                'provider' => array('type' => 'text', 'description' => 'name of this oEmbed provider'),
                'provider_url' => array('type' => 'text', 'description' => 'URL of this oEmbed provider'),
                'width' => array('type' => 'int', 'description' => 'width of oEmbed resource when available'),
                'height' => array('type' => 'int', 'description' => 'height of oEmbed resource when available'),
                'html' => array('type' => 'text', 'description' => 'html representation of this oEmbed resource when applicable'),
                'title' => array('type' => 'text', 'description' => 'title of oEmbed resource when available'),
                'author_name' => array('type' => 'text', 'description' => 'author name for this oEmbed resource'),
                'author_url' => array('type' => 'text', 'description' => 'author URL for this oEmbed resource'),
                'url' => array('type' => 'text', 'description' => 'URL for this oEmbed resource when applicable (photo, link)'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('file_id'),
            'foreign keys' => array(
                'file_embed_file_id_fkey' => array('file', array('file_id' => 'id')),
            ),
        );
    }

    public static function _getOembed($url)
    {
        try {
            return oEmbedHelper::getObject($url);
        } catch (Exception $e) {
            common_log(LOG_INFO, "Error during oembed lookup for $url - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch an entry by using a File's id
     */
    public static function getByFile(File $file)
    {
        $fo = new File_embed();
        $fo->file_id = $file->id;
        if (!$fo->find(true)) {
            throw new NoResultException($fo);
        }
        return $fo;
    }

    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Save embedding info for a new file.
     *
     * @param object $data Services_oEmbed_Object_*
     * @param int $file_id
     */
    public static function saveNew($data, $file_id)
    {
        $file_embed = new File_embed;
        $file_embed->file_id = $file_id;
        if (!isset($data->version)) {
            common_debug('DEBUGGING oEmbed: data->version undefined in variable $data: '.var_export($data, true));
        }
        $file_embed->version = $data->version;
        $file_embed->type = $data->type;
        if (!empty($data->provider_name)) {
            $file_embed->provider = $data->provider_name;
        }
        if (!empty($data->provider)) {
            $file_embed->provider = $data->provider;
        }
        if (!empty($data->provider_url)) {
            $file_embed->provider_url = $data->provider_url;
        }
        if (!empty($data->width)) {
            $file_embed->width = intval($data->width);
        }
        if (!empty($data->height)) {
            $file_embed->height = intval($data->height);
        }
        if (!empty($data->html)) {
            $file_embed->html = $data->html;
        }
        if (!empty($data->title)) {
            $file_embed->title = $data->title;
        }
        if (!empty($data->author_name)) {
            $file_embed->author_name = $data->author_name;
        }
        if (!empty($data->author_url)) {
            $file_embed->author_url = $data->author_url;
        }
        if (!empty($data->url)) {
            $file_embed->url = $data->url;
            $given_url = File_redirection::_canonUrl($file_embed->url);
            if (! empty($given_url)) {
                try {
                    $file = File::getByUrl($given_url);
                    $file_embed->mimetype = $file->mimetype;
                } catch (NoResultException $e) {
                    // File_redirection::where argument 'discover' is false to avoid loops
                    $redir = File_redirection::where($given_url, false);
                    if (!empty($redir->file_id)) {
                        $file_id = $redir->file_id;
                    }
                }
            }
        }
        $result = $file_embed->insert();
        if ($result === false) {
            throw new ServerException('Failed to insert File_embed data into database!');
        }
        if (!empty($data->thumbnail_url) || ($data->type == 'photo')) {
            $ft = File_thumbnail::getKV('file_id', $file_id);
            if ($ft instanceof File_thumbnail) {
                common_log(
                    LOG_WARNING,
                    "Strangely, a File_thumbnail object exists for new file $file_id",
                    __FILE__
                );
            } else {
                File_thumbnail::saveNew($data, $file_id);
            }
        }
    }
}
