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

/*
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Table Definition for file_thumbnail
 */
class File_thumbnail extends Managed_DataObject
{
    public $__table = 'file_thumbnail';                  // table name
    public $file_id;                         // int(4)  primary_key not_null
    public $urlhash;                         // varchar(64) indexed
    public $url;                             // text
    public $filename;                        // text
    public $width;                           // int(4)  primary_key
    public $height;                          // int(4)  primary_key
    public $modified;                        // timestamp() not_null default_CURRENT_TIMESTAMP

    const URLHASH_ALG = 'sha256';

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'file_id' => array('type' => 'int', 'not null' => true, 'description' => 'thumbnail for what URL/file'),
                'urlhash' => array('type' => 'varchar', 'length' => 64, 'description' => 'sha256 of url field if non-empty'),
                'url' => array('type' => 'text', 'description' => 'URL of thumbnail'),
                'filename' => array('type' => 'text', 'description' => 'if stored locally, filename is put here'),
                'width' => array('type' => 'int', 'not null' => true, 'description' => 'width of thumbnail'),
                'height' => array('type' => 'int', 'not null' => true, 'description' => 'height of thumbnail'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('file_id', 'width', 'height'),
            'indexes' => array(
                'file_thumbnail_urlhash_idx' => array('urlhash'),
            ),
            'foreign keys' => array(
                'file_thumbnail_file_id_fkey' => array('file', array('file_id' => 'id')),
            )
        );
    }

    /**
     * Get the attachment's thumbnail record, if any or generate one.
     *
     * @param File $file
     * @param int|null $width    Max width of thumbnail in pixels. (if null, use common_config values)
     * @param int|null $height   Max height of thumbnail in pixels. (if null, square-crop to $width)
     * @param bool $crop         Crop to the max-values' aspect ratio
     * @param bool $force_still  Don't allow fallback to showing original (such as animated GIF)
     * @param bool|null $upscale Whether or not to scale smaller images up to larger thumbnail sizes. (null = site default)
     *
     * @return File_thumbnail
     *
     * @throws ClientException
     * @throws FileNotFoundException
     * @throws FileNotStoredLocallyException
     * @throws InvalidFilenameException
     * @throws NoResultException
     * @throws ServerException on various other errors
     * @throws UnsupportedMediaException if, despite trying, we can't understand how to make a thumbnail for this format
     * @throws UseFileAsThumbnailException if the file is considered an image itself and should be itself as thumbnail
     */
    public static function fromFileObject(
        File $file,
        ?int $width = null,
        ?int $height = null,
        bool $crop = false,
        bool $force_still = true,
        ?bool $upscale = null
    ): File_thumbnail {
        // Is file stored remotely only?
        $was_stored_remotely = $file->isStoredRemotely();

        // If StoreRemoteMedia or Embed are enabled (they will only act if appropriate btw)...
        $media = common_get_mime_media($file->mimetype);
        Event::handle('CreateFileImageThumbnailSource', [$file, &$imgPath, $media]);

        // If it was stored remotely, we can now assume it was sufficiently retrieved
        if ($was_stored_remotely) {
            $file = File::getById($file->getID());
        }

        if (file_exists($imgPath)) {
            $image = new ImageFile($file->getID(), $imgPath, null, $file->getUrl(false));
        } else {
            try {
                $image = ImageFile::fromFileObject($file);
            } catch (InvalidFilenameException $e) {
                // Not having an original local file doesn't mean we don't have a thumbnail.
                $existing_thumb = File_thumbnail::byFile($file);
                $image = new ImageFile($file->getID(), $existing_thumb->getPath(), null, $existing_thumb->url);
            }
        }

        if ($image->animated && !common_config('thumbnail', 'animated')) {
            // null  means "always use file as thumbnail"
            // false means you get choice between frozen frame or original when calling getThumbnail
            if (is_null(common_config('thumbnail', 'animated')) || !$force_still) {
                try {
                    // remote files with animated GIFs as thumbnails will match this
                    return File_thumbnail::byFile($file);
                } catch (NoResultException $e) {
                    // and if it's not a remote file, it'll be safe to use the locally stored File
                    throw new UseFileAsThumbnailException($file);
                }
            }
        }

        return $image->getFileThumbnail(
            $width,
            $height,
            $crop,
            !is_null($upscale) ? $upscale : common_config('thumbnail', 'upscale')
        );
    }

    /**
     * Save oEmbed-provided thumbnail data
     *
     * @param object $data
     * @param int $file_id
     */
    public static function saveNew($data, $file_id)
    {
        if (!empty($data->thumbnail_url)) {
            // Non-photo types such as video will usually
            // show us a thumbnail, though it's not required.
            self::saveThumbnail(
                $file_id,
                $data->thumbnail_url,
                $data->thumbnail_width,
                $data->thumbnail_height
            );
        } elseif ($data->type == 'photo') {
            // The inline photo URL given should also fit within
            // our requested thumbnail size, per oEmbed spec.
            self::saveThumbnail(
                $file_id,
                $data->url,
                $data->width,
                $data->height
            );
        }
    }

    /**
     * Fetch an entry by using a File's id
     *
     * @param   File    $file       The File object we're getting a thumbnail for.
     * @param   boolean $notNullUrl Originally remote thumbnails have a URL stored, we use this to find the "original"
     *
     * @return  File_thumbnail
     * @throws  NoResultException if no File_thumbnail matched the criteria
     */
    public static function byFile(File $file, $notNullUrl = true)
    {
        $thumb = new File_thumbnail();
        $thumb->file_id = $file->getID();
        if ($notNullUrl) {
            $thumb->whereAdd('url IS NOT NULL');
        }
        $thumb->orderBy('modified ASC');    // the first created, a somewhat ugly hack
        $thumb->limit(1);
        if (!$thumb->find(true)) {
            throw new NoResultException($thumb);
        }
        return $thumb;
    }

    /**
     * Save a thumbnail record for the referenced file record.
     *
     * FIXME: Add error handling
     *
     * @param int $file_id
     * @param string $url
     * @param int $width
     * @param int $height
     */
    public static function saveThumbnail($file_id, $url, $width, $height, $filename = null)
    {
        $tn = new File_thumbnail;
        $tn->file_id = $file_id;
        $tn->url = $url;
        $tn->filename = $filename;
        $tn->width = (int)$width;
        $tn->height = (int)$height;
        $tn->insert();
        return $tn;
    }

    public static function path($filename): string
    {
        File::tryFilename($filename);

        // NOTE: If this is left empty in default config, it will be set to File::path('thumb')
        $dir = common_config('thumbnail', 'dir');

        if (!in_array($dir[mb_strlen($dir)-1], ['/', '\\'])) {
            $dir .= DIRECTORY_SEPARATOR;
        }

        return $dir . $filename;
    }

    public function getFilename()
    {
        return File::tryFilename($this->filename);
    }

    /**
     * @return  string  full filesystem path to the locally stored thumbnail file
     * @throws FileNotFoundException
     * @throws ServerException
     */
    public function getPath(): string
    {
        $oldpath = File::path($this->getFilename());
        $thumbpath = self::path($this->getFilename());

        // If we have a file in our old thumbnail storage path, move (or copy) it to the new one
        // (if the if/elseif don't match, we have a $thumbpath just as we should and can return it)
        if (file_exists($oldpath) && !file_exists($thumbpath)) {
            try {
                // let's get the filename of the File, to check below if it happens to be identical
                $file_filename = $this->getFile()->getFilename();
            } catch (NoResultException $e) {
                // reasonably the function calling us will handle the following as "File_thumbnail entry should be deleted"
                throw new FileNotFoundException($thumbpath);
            } catch (InvalidFilenameException $e) {
                // invalid filename in getFile()->getFilename(), just
                // means the File object isn't stored locally and that
                // means it's safe to move it below.
                $file_filename = null;
            }

            if ($this->getFilename() === $file_filename) {
                // special case where thumbnail file exactly matches stored File filename
                common_debug('File filename and File_thumbnail filename match on '.$this->file_id.', copying instead');
                copy($oldpath, $thumbpath);
            } elseif (!rename($oldpath, $thumbpath)) {
                common_log(LOG_ERR, 'Could not move thumbnail from '._ve($oldpath).' to '._ve($thumbpath));
                throw new ServerException('Could not move thumbnail from old path to new path.');
            } else {
                common_log(LOG_DEBUG, 'Moved thumbnail '.$this->file_id.' from '._ve($oldpath).' to '._ve($thumbpath));
            }
        } elseif (!file_exists($thumbpath)) {
            throw new FileNotFoundException($thumbpath);
        }

        return $thumbpath;
    }

    public function getUrl()
    {
        $url = common_local_url('attachment_thumbnail', ['attachment' => $this->getFile()->getID()]);
        if (strpos($url, '?') === false) {
            $url .= '?';
        }
        return $url . http_build_query(['w'=>$this->width, 'h'=>$this->height]);
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @throws UseFileAsThumbnailException from File_thumbnail->getUrl() for stuff like animated GIFs
     */
    public function getHtmlAttrs(array $orig=array(), $overwrite=true)
    {
        $attrs = [ 'height' => $this->getHeight(),
                   'width'  => $this->getWidth(),
                   'src'    => $this->getUrl() ];
        return $overwrite ? array_merge($orig, $attrs) : array_merge($attrs, $orig);
    }

    public function delete($useWhere=false)
    {
        try {
            $thumbpath = self::path($this->getFilename());
            // if file does not exist, try to delete it
            $deleted = !file_exists($thumbpath) || @unlink($thumbpath);
            if (!$deleted) {
                common_log(LOG_ERR, 'Could not unlink existing thumbnail file: '._ve($thumbpath));
            }
        } catch (InvalidFilenameException $e) {
            common_log(LOG_ERR, 'Deleting object but not attempting deleting file: '._ve($e->getMessage()));
        }

        return parent::delete($useWhere);
    }

    public function getFile(): File
    {
        return File::getByID($this->file_id);
    }

    public function getFileId()
    {
        return $this->file_id;
    }

    public static function hashurl($url)
    {
        if (!mb_strlen($url)) {
            throw new Exception('No URL provided to hash algorithm.');
        }
        return hash(self::URLHASH_ALG, $url);
    }

    public function onInsert()
    {
        $this->setUrlhash();
    }

    public function onUpdate($dataObject=false)
    {
        // if we have nothing to compare with OR it has changed from previous entry
        if (!$dataObject instanceof Managed_DataObject || $this->url !== $dataObject->url) {
            $this->setUrlhash();
        }
    }

    public function setUrlhash()
    {
        $this->urlhash = mb_strlen($this->url)>0 ? self::hashurl($this->url) : null;
    }
}
