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

namespace Plugin\StoreRemoteMedia;

use App\Core\Modules\Plugin;

/**
 * The StoreRemoteMedia plugin downloads remotely attached files to local server.
 *
 * @package   GNUsocial
 *
 * @author    Mikael Nordfeldth
 * @author    Stephen Paul Weber
 * @author    Mikael Nordfeldth
 * @author    Miguel Dantas
 * @author    Diogo Peralta Cordeiro
 * @copyright 2015-2016, 2019-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class StoreRemoteMedia extends Plugin
{
    const PLUGIN_VERSION = '3.0.0';

    // settings which can be set in config.php with addPlugin('StoreRemoteMedia', array('param'=>'value', ...));
    // WARNING, these are _regexps_ (slashes added later). Always escape your dots and end your strings
    public $domain_whitelist = [
        // hostname             => service provider
        '^i\d*\.ytimg\.com$'    => 'YouTube',
        '^i\d*\.vimeocdn\.com$' => 'Vimeo',
    ];

    public $append_whitelist = [];    // fill this array as domain_whitelist to add more trusted sources
    public $check_whitelist  = false; // security/abuse precaution

    public $store_original = false; // Whether to maintain a copy of the original media or only a thumbnail of it
    public $thumbnail_width;
    public $thumbnail_height;
    public $crop;
    public $max_size;

    /**
     * Initialize the StoreRemoteMedia plugin and set up the environment it needs for it.
     * Returns true if it initialized properly, the exception object if it
     * doesn't.
     */
    public function initialize()
    {
        parent::initialize();

        $this->domain_whitelist = array_merge($this->domain_whitelist, $this->append_whitelist);

        // Load global configuration if specific not provided
        $this->thumbnail_width  = $this->thumbnail_width  ?? common_config('thumbnail', 'width');
        $this->thumbnail_height = $this->thumbnail_height ?? common_config('thumbnail', 'height');
        $this->max_size         = $this->max_size         ?? common_config('attachments', 'file_quota');
        $this->crop             = $this->crop             ?? common_config('thumbnail', 'crop');
    }

    /**
     * This event executes when GNU social is creating a file thumbnail entry in
     * the database.  We glom onto this to fetch remote attachments.
     *
     * @param $file File the file of the created thumbnail
     * @param &$imgPath null|string = out the path to the created thumbnail (output parameter)
     * @param $media string = media type (unused)
     *
     * @throws AlreadyFulfilledException
     * @throws FileNotFoundException
     * @throws FileNotStoredLocallyException
     * @throws HTTP_Request2_Exception
     * @throws ServerException
     *
     * @return bool
     */
    public function onCreateFileImageThumbnailSource(File $file, ?string &$imgPath = null, ?string $media = null): bool
    {
        // If we are on a private node, we won't do any remote calls (just as a precaution until
        // we can configure this from config.php for the private nodes)
        if (common_config('site', 'private')) {
            return true;
        }

        // If there is a local filename, it is either a local file already or has already been downloaded.
        if (!$file->isStoredRemotely()) {
            common_debug(sprintf('File id==%d isn\'t a non-fetched remote file (%s), so nothing StoreRemoteMedia ' .
                'should handle.', $file->getID(), _ve($file->filename)));
            return true;
        }

        try {
            File_thumbnail::byFile($file);
            // If we don't get the exception `No result found on File_thumbnail lookup.` then Embed has already handled it most likely.
            return true;
        } catch (NoResultException $e) {
            // We can move on
        }

        $url = $file->getUrl(false);

        if (substr($url, 0, 7) == 'file://') {
            $filename = substr($url, 7);
            $info     = getimagesize($filename);
            $filename = basename($filename);
            $width    = $info[0];
            $height   = $info[1];
        } else {
            $this->checkWhitelist($url);
            $head    = (new HTTPClient())->head($url);
            $headers = $head->getHeader();
            $headers = array_change_key_case($headers, CASE_LOWER);

            try {
                $is_image = $this->isRemoteImage($url, $headers);
                if ($is_image == true) {
                    $file_size = $this->getRemoteFileSize($url, $headers);
                    if (($file_size != false) && ($file_size > $this->max_size)) {
                        common_debug('Went to store remote thumbnail of size ' . $file_size .
                            ' but the upload limit is ' . $this->max_size . ' so we aborted.');
                        return false;
                    }
                } else {
                    return false;
                }
            } catch (Exception $err) {
                common_debug('Could not determine size of remote image, aborted local storage.');
                throw $err;
            }

            // First we download the file to memory and test whether it's actually an image file
            // FIXME: To support remote video/whatever files, this needs reworking.
            common_debug(sprintf(
                'Downloading remote image for file id==%u with URL: %s',
                $file->getID(),
                $url
            ));
            try {
                $imgData = HTTPClient::quickGet($url);
                if (isset($imgData)) {
                    list($filename, $filehash, $width, $height) = $this->validateAndWriteImage(
                        $imgData,
                        $url,
                        $headers,
                        $file->getID()
                    );
                } else {
                    throw new UnsupportedMediaException('HTTPClient returned an empty result');
                }
            } catch (UnsupportedMediaException $e) {
                // Couldn't find anything that looks like an image, nothing to do
                common_debug("StoreRemoteMedia was not able to find an image for URL `{$url}`: " . $e->getMessage());
                return false;
            }
        }

        $ft = null;
        if ($this->store_original) {
            try {
                // Update our database for the file record
                $orig           = clone $file;
                $file->filename = $filename;
                $file->filehash = $filehash;
                $file->width    = $width;
                $file->height   = $height;
                // Throws exception on failure.
                $file->updateWithKeys($orig);
            } catch (Exception $err) {
                common_log(LOG_ERR, 'Went to update a file entry on the database in ' .
                    'StoreRemoteMediaPlugin::storeRemoteThumbnail but encountered error: ' . $err);
                throw $err;
            }
        } else {
            try {
                // Insert a thumbnail record for this file
                $data                   = new stdClass();
                $data->thumbnail_url    = $url;
                $data->thumbnail_width  = $width;
                $data->thumbnail_height = $height;
                File_thumbnail::saveNew($data, $file->getID());
                $ft           = File_thumbnail::byFile($file);
                $orig         = clone $ft;
                $ft->filename = $filename;
                $ft->updateWithKeys($orig);
            } catch (Exception $err) {
                common_log(LOG_ERR, 'Went to write a thumbnail entry to the database in ' .
                    'StoreRemoteMediaPlugin::storeRemoteThumbnail but encountered error: ' . $err);
                throw $err;
            }
        }

        // Out
        try {
            $imgPath = $file->getFileOrThumbnailPath($ft);
            return !file_exists($imgPath);
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Check the file size of a remote file using a HEAD request and checking
     * the content-length variable returned.  This isn't 100% foolproof but is
     * reliable enough for our purposes.
     *
     * @param mixed      $url
     * @param null|mixed $headers
     *
     * @return bool|string the file size if it succeeds, false otherwise.
     */
    private function getRemoteFileSize($url, $headers = null)
    {
        try {
            if ($headers === null) {
                if (!common_valid_http_url($url)) {
                    common_log(LOG_ERR, 'Invalid URL in StoreRemoteMedia::getRemoteFileSize()');
                    return false;
                }
                $head    = (new HTTPClient())->head($url);
                $headers = $head->getHeader();
                $headers = array_change_key_case($headers, CASE_LOWER);
            }
            return $headers['content-length'] ?? false;
        } catch (Exception $err) {
            common_log(LOG_ERR, __CLASS__ . ': getRemoteFileSize on URL : ' . _ve($url) .
                ' threw exception: ' . $err->getMessage());
            return false;
        }
    }

    /**
     * A private helper function that uses a CURL lookup to check the mime type
     * of a remote URL to see it it's an image.
     *
     * @param mixed      $url
     * @param null|mixed $headers
     *
     * @return bool true if the remote URL is an image, or false otherwise.
     */
    private function isRemoteImage($url, $headers = null): bool
    {
        if (empty($headers)) {
            if (!common_valid_http_url($url)) {
                common_log(LOG_ERR, 'Invalid URL in StoreRemoteMedia::isRemoteImage()');
                return false;
            }
            $head    = (new HTTPClient())->head($url);
            $headers = $head->getHeader();
            $headers = array_change_key_case($headers, CASE_LOWER);
        }
        return !empty($headers['content-type']) && common_get_mime_media($headers['content-type']) === 'image';
    }

    /**
     * Validate that $imgData is a valid image before writing it to
     * disk, as well as resizing it to at most $this->thumbnail_width
     * by $this->thumbnail_height
     *
     * @param $imgData - The image data to validate. Taken by reference to avoid copying
     * @param null|string $url     - The url where the image came from, to fetch metadata
     * @param null|array  $headers - The headers possible previous request to $url
     * @param null|int    $file_id - The id of the file this image belongs to, used for logging
     */
    protected function validateAndWriteImage(&$imgData, ?string $url = null, ?array $headers = null, ?int $file_id = null): array
    {
        $info = @getimagesizefromstring($imgData);
        // array indexes documented on php.net:
        // https://php.net/manual/en/function.getimagesize.php
        if ($info === false) {
            throw new UnsupportedMediaException(_m('Remote file format was not identified as an image.'), $url);
        } elseif (!$info[0] || !$info[1]) {
            throw new UnsupportedMediaException(_m('Image file had impossible geometry (0 width or height)'));
        }

        $width    = min($info[0], $this->thumbnail_width);
        $height   = min($info[1], $this->thumbnail_height);
        $filehash = hash(File::FILEHASH_ALG, $imgData);

        try {
            if (!empty($url)) {
                $original_name = HTTPClient::get_filename($url, $headers);
            }
            $filename = MediaFile::encodeFilename($original_name ?? _m('Untitled attachment'), $filehash);
        } catch (Exception $err) {
            common_log(LOG_ERR, 'Went to write a thumbnail to disk in StoreRemoteMediaPlugin::storeRemoteThumbnail ' .
                "but encountered error: {$err}");
            throw $err;
        }

        try {
            $fullpath = $this->store_original ? File::path($filename) : File_thumbnail::path($filename);
            // Write the file to disk. Throw Exception on failure
            if (!file_exists($fullpath)) {
                if (strpos($fullpath, INSTALLDIR) !== 0 || file_put_contents($fullpath, $imgData) === false) {
                    throw new ServerException(_m('Could not write downloaded file to disk.'));
                }

                if (common_get_mime_media(MediaFile::getUploadedMimeType($fullpath)) !== 'image') {
                    @unlink($fullpath);
                    throw new UnsupportedMediaException(
                        _m('Remote file format was not identified as an image.'),
                        $url
                    );
                }

                // If the image is not of the desired size, resize it
                if (!$this->store_original && $this->crop && ($info[0] > $this->thumbnail_width || $info[1] > $this->thumbnail_height)) {
                    try {
                        // Temporary object, not stored in DB
                        $img                                  = new ImageFile(-1, $fullpath);
                        list($width, $height, $x, $y, $w, $h) = $img->scaleToFit($this->thumbnail_width, $this->thumbnail_height, $this->crop);

                        // The boundary box for our resizing
                        $box = [
                            'width' => $width, 'height' => $height,
                            'x'     => $x, 'y' => $y,
                            'w'     => $w, 'h' => $h,
                        ];

                        $width  = $box['width'];
                        $height = $box['height'];
                        $img->resizeTo($fullpath, $box);
                    } catch (\Intervention\Image\Exception\NotReadableException $e) {
                        common_log(LOG_ERR, "StoreRemoteMediaPlugin::storeRemoteThumbnail was unable to decode image with Intervention: {$e}");
                        // No need to interrupt processing
                    }
                }
            } else {
                throw new AlreadyFulfilledException('A thumbnail seems to already exist for remote file' .
                    ($file_id ? 'with id==' . $file_id : '') . ' at path ' . $fullpath);
            }
        } catch (AlreadyFulfilledException $e) {
            // Carry on
        } catch (Exception $err) {
            common_log(LOG_ERR, 'Went to write a thumbnail to disk in StoreRemoteMediaPlugin::storeRemoteThumbnail ' .
                "but encountered error: {$err}");
            throw $err;
        } finally {
            unset($imgData);
        }

        return [$filename, $filehash, $width, $height];
    }

    /**
     * @param mixed $url
     *
     * @throws ServerException if check is made but fails
     *
     * @return bool false on no check made, provider name on success
     */
    protected function checkWhitelist($url)
    {
        if (!$this->check_whitelist) {
            return false;   // indicates "no check made"
        }

        $host = parse_url($url, PHP_URL_HOST);
        foreach ($this->domain_whitelist as $regex => $provider) {
            if (preg_match("/{$regex}/", $host)) {
                return $provider;    // we trust this source, return provider name
            }
        }

        throw new ServerException(sprintf(_m('Domain not in remote thumbnail source whitelist: %s'), $host));
    }

    /**
     * Event raised when GNU social polls the plugin for information about it.
     * Adds this plugin's version information to $versions array
     *
     * @param &$versions array inherited from parent
     *
     * @return bool true hook value
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = ['name' => 'StoreRemoteMedia',
            'version'         => self::PLUGIN_VERSION,
            'author'          => 'Mikael Nordfeldth, Diogo Peralta Cordeiro',
            'homepage'        => GNUSOCIAL_ENGINE_URL,
            'description'     => // TRANS: Plugin description.
            _m('Plugin for downloading remotely attached files to local server.'), ];
        return true;
    }
}
