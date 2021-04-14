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
 * OEmbed and OpenGraph implementation for GNU social
 *
 * @package   GNUsocial
 *
 * @author    Mikael Nordfeldth
 * @author    Stephen Paul Weber
 * @author    hannes
 * @author    Mikael Nordfeldth
 * @author    Diogo Peralta Cordeiro
 * @author    Miguel Dantas
 * @author    Diogo Peralta Cordeiro
 * @copyright 2014-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\Embed;

use App\Core\Modules\Module;

/**
 * Base class for the Embed plugin that does most of the heavy lifting to get
 * and display representations for remote content.
 *
 * @copyright 2014-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Embed extends Module
{
    const PLUGIN_VERSION = '2.1.0';

    // settings which can be set in config.php with addPlugin('Embed', ['param'=>'value', ...]);
    // WARNING, these are _regexps_ (slashes added later). Always escape your dots and end ('$') your strings

    public $domain_whitelist = [
        // hostname => service provider
        '^i\d*\.ytimg\.com$'    => 'YouTube',
        '^i\d*\.vimeocdn\.com$' => 'Vimeo',
    ];
    public $append_whitelist = [];  // fill this array as domain_whitelist to add more trusted sources
    public $check_whitelist  = false;    // security/abuse precaution

    public $thumbnail_width  = 128;
    public $thumbnail_height = 128;
    public $crop             = true;
    public $max_size;

    protected $imgData = [];

    /**
     * Initialize the Embed plugin and set up the environment it needs for it.
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
     * The code executed on GNU social checking the database schema, which in
     * this case is to make sure we have the plugin table we need.
     *
     * @return bool true if it ran successfully, the exception object if it doesn't.
     */
    public function onCheckSchema()
    {
        $this->onEndUpgrade(); // Ensure rename

        $schema = Schema::get();
        $schema->ensureTable('file_embed', File_embed::schemaDef());
        return true;
    }

    public function onEndUpgrade()
    {
        $schema = Schema::get();
        return $schema->renameTable('file_oembed', 'file_embed');
    }

    /**
     * This code executes when GNU social creates the page routing, and we hook
     * on this event to add our action handler for Embed.
     *
     * @param $m URLMapper the router that was initialized.
     *
     * @throws Exception
     *
     * @return void true if successful, the exception object if it isn't.
     */
    public function onRouterInitialized(URLMapper $m)
    {
        $m->connect('main/oembed', ['action' => 'oembed']);
    }

    /**
     * This event executes when GNU social encounters a remote URL we then decide
     * to interrogate for metadata. Embed gloms onto it to see if we have an
     * oEmbed endpoint or image to try to represent in the post.
     *
     * @param $url string        the remote URL we're looking at
     * @param $dom DOMDocument   the document we're getting metadata from
     * @param $metadata stdClass class representing the metadata
     *
     * @return bool true if successful, the exception object if it isn't.
     */
    public function onGetRemoteUrlMetadataFromDom(string $url, DOMDocument $dom, stdClass &$metadata)
    {
        try {
            common_log(LOG_INFO, "Trying to find Embed data for {$url} with 'oscarotero/Embed'");
            $info = self::create($url);

            $metadata->version          = '1.0'; // Yes.
            $metadata->provider_name    = $info->authorName;
            $metadata->title            = $info->title;
            $metadata->html             = common_purify($info->description);
            $metadata->type             = $info->type;
            $metadata->url              = $info->url;
            $metadata->thumbnail_height = $info->imageHeight;
            $metadata->thumbnail_width  = $info->imageWidth;

            if (substr($info->image, 0, 4) === 'data') {
                // Inline image
                $imgData        = base64_decode(substr($info->image, stripos($info->image, 'base64,') + 7));
                list($filename) = $this->validateAndWriteImage($imgData);
                // Use a file URI for images, as file_embed can't store a filename
                $metadata->thumbnail_url = 'file://' . File_thumbnail::path($filename);
            } else {
                $metadata->thumbnail_url = $info->image;
            }
        } catch (Exception $e) {
            common_log(LOG_INFO, "Failed to find Embed data for {$url} with 'oscarotero/Embed'" .
                ', got exception: ' . get_class($e));
        }

        if (isset($metadata->thumbnail_url)) {
            // sometimes sites serve the path, not the full URL, for images
            // let's "be liberal in what you accept from others"!
            // add protocol and host if the thumbnail_url starts with /
            if ($metadata->thumbnail_url[0] == '/') {
                $thumbnail_url_parsed    = parse_url($metadata->url);
                $metadata->thumbnail_url = "{$thumbnail_url_parsed['scheme']}://" .
                    "{$thumbnail_url_parsed['host']}$metadata->thumbnail_url";
            }

            // some wordpress opengraph implementations sometimes return a white blank image
            // no need for us to save that!
            if ($metadata->thumbnail_url == 'https://s0.wp.com/i/blank.jpg') {
                $metadata->thumbnail_url = null;
            }

            // FIXME: this is also true of locally-installed wordpress so we should watch out for that.
        }
        return true;
    }

    /** Placeholder */
    public function onEndShowHeadElements(Action $action)
    {
        switch ($action->getActionName()) {
            case 'attachment':
                $url = common_local_url('attachment', ['attachment' => $action->attachment->getID()]);
                break;
            case 'shownotice':
                if (!$action->notice->isLocal()) {
                    return true;
                }
                try {
                    $url = $action->notice->getUrl();
                } catch (InvalidUrlException $e) {
                    // The notice is probably a share or similar, which don't
                    // have a representational URL of their own.
                    return true;
                }
                break;
        }

        if (isset($url)) {
            foreach (['xml', 'json'] as $format) {
                $action->element(
                    'link',
                    [
                        'rel'   => 'alternate',
                        'type'  => "application/{$format}+oembed",
                        'href'  => common_local_url('oembed', [], ['format' => $format, 'url' => $url]),
                        'title' => 'oEmbed',
                    ]
                );
            }
        }
        return true;
    }

    /** Placeholder */
    public function onEndShowStylesheets(Action $action)
    {
        $action->cssLink($this->path('css/embed.css'));
        return true;
    }

    /**
     * Save embedding information for a File, if applicable.
     *
     * Normally this event is called through File::saveNew()
     *
     * @param File $file The newly inserted File object.
     *
     * @return bool success
     */
    public function onEndFileSaveNew(File $file)
    {
        $fe = File_embed::getKV('file_id', $file->getID());
        if ($fe instanceof File_embed) {
            common_log(LOG_WARNING, "Strangely, a File_embed object exists for new file {$file->getID()}", __FILE__);
            return true;
        }

        if (isset($file->mimetype)
            && (('text/html' === substr($file->mimetype, 0, 9) || 'application/xhtml+xml' === substr($file->mimetype, 0, 21)))) {
            try {
                $embed_data = File_embed::getEmbed($file->url);
                if ($embed_data === false) {
                    throw new Exception("Did not get Embed data from URL {$file->url}");
                }
                $file->setTitle($embed_data->title);
            } catch (Exception $e) {
                common_log(LOG_WARNING, sprintf(
                    __METHOD__ . ': %s thrown when getting embed data: %s',
                    get_class($e),
                    _ve($e->getMessage())
                ));
                return true;
            }

            File_embed::saveNew($embed_data, $file->getID());
        }
        return true;
    }

    /** Placeholder */
    public function onEndShowAttachmentLink(HTMLOutputter $out, File $file)
    {
        $embed = File_embed::getKV('file_id', $file->getID());
        if (empty($embed->author_name) && empty($embed->provider)) {
            return true;
        }
        $out->elementStart('div', ['id' => 'oembed_info', 'class' => 'e-content']);
        foreach (['author_name' => ['class' => ' author', 'url' => 'author_url'],
            'provider'          => ['class' => '',        'url' => 'provider_url'], ]
                 as $field => $options) {
            if (!empty($embed->{$field})) {
                $out->elementStart('div', 'fn vcard' . $options['class']);
                if (empty($embed->{$options['url']})) {
                    $out->text($embed->{$field});
                } else {
                    $out->element(
                        'a',
                        ['href'     => $embed->{$options['url']},
                            'class' => 'url', ],
                        $embed->{$field}
                    );
                }
            }
        }
        $out->elementEnd('div');
        return false;
    }

    /** Placeholder */
    public function onFileEnclosureMetadata(File $file, &$enclosure)
    {
        // Never treat generic HTML links as an enclosure type!
        // But if we have embed info, we'll consider it golden.
        $embed = File_embed::getKV('file_id', $file->getID());
        if (!$embed instanceof File_embed || !in_array($embed->type, ['photo', 'video'])) {
            return true;
        }

        foreach (['mimetype', 'url', 'title', 'modified', 'width', 'height'] as $key) {
            if (isset($embed->{$key}) && !empty($embed->{$key})) {
                $enclosure->{$key} = $embed->{$key};
            }
        }
        return true;
    }

    /** Placeholder */
    public function onStartShowAttachmentRepresentation(HTMLOutputter $out, File $file)
    {
        try {
            $embed = File_embed::getByFile($file);
        } catch (NoResultException $e) {
            return true;
        }

        // Show thumbnail as usual if it's a photo.
        if ($embed->type === 'photo') {
            return true;
        }

        $out->elementStart('article', ['class' => 'h-entry embed']);
        $out->elementStart('header');
        try {
            $thumb = $file->getThumbnail($this->thumbnail_width, $this->thumbnail_height);
            $out->element('img', $thumb->getHtmlAttrs(['class' => 'u-photo embed']));
            unset($thumb);
        } catch (FileNotFoundException $e) {
            // Nothing to show
        } catch (Exception $e) {
            $out->element('div', ['class' => 'error'], $e->getMessage());
        }
        $out->elementStart('h5', ['class' => 'p-name embed']);
        $out->element('a', ['class' => 'u-url', 'href' => $file->getUrl()], common_strip_html($embed->title));
        $out->elementEnd('h5');
        $out->elementStart('div', ['class' => 'p-author embed']);
        if (!empty($embed->author_name)) {
            // TRANS: text before the author name of embed attachment representation
            // FIXME: The whole "By x from y" should be i18n because of different language constructions.
            $out->text(_('By '));
            $attrs = ['class' => 'h-card p-author'];
            if (!empty($embed->author_url)) {
                $attrs['href'] = $embed->author_url;
                $tag           = 'a';
            } else {
                $tag = 'span';
            }
            $out->element($tag, $attrs, $embed->author_name);
        }
        if (!empty($embed->provider)) {
            // TRANS: text between the embed author name and provider url
            // FIXME: The whole "By x from y" should be i18n because of different language constructions.
            $out->text(_(' from '));
            $attrs = ['class' => 'h-card'];
            if (!empty($embed->provider_url)) {
                $attrs['href'] = $embed->provider_url;
                $tag           = 'a';
            } else {
                $tag = 'span';
            }
            $out->element($tag, $attrs, $embed->provider);
        }
        $out->elementEnd('div');
        $out->elementEnd('header');
        $out->elementStart('div', ['class' => 'p-summary embed']);
        $out->raw(common_purify($embed->html));
        $out->elementEnd('div');
        $out->elementStart('footer');
        $out->elementEnd('footer');
        $out->elementEnd('article');

        return false;
    }

    /** Placeholder */
    public function onShowUnsupportedAttachmentRepresentation(HTMLOutputter $out, File $file)
    {
        try {
            $embed = File_embed::getByFile($file);
        } catch (NoResultException $e) {
            return true;
        }

        // the 'photo' type is shown through ordinary means, using StartShowAttachmentRepresentation!
        switch ($embed->type) {
            case 'video':
            case 'link':
                if (!empty($embed->html)
                    && (GNUsocial::isAjax() || common_config('attachments', 'show_html'))) {
                    $purifier = new HTMLPurifier();
                    // FIXME: do we allow <object> and <embed> here? we did that when we used htmLawed,
                    // but I'm not sure anymore...
                    $out->raw($purifier->purify($embed->html));
                }
                return false;
        }

        return true;
    }

    /**
     * This event executes when GNU social is creating a file thumbnail entry in
     * the database.  We glom onto this to create proper information for Embed
     * object thumbnails.
     *
     * @param $file File the file of the created thumbnail
     * @param &$imgPath null|string = the path to the created thumbnail (output)
     * @param $media string = media type
     *
     * @throws FileNotFoundException
     * @throws NoResultException
     * @throws ServerException
     *
     * @return bool true if it succeeds (including non-action
     *              states where it isn't oEmbed data, so it doesn't mess up the event handle
     *              for other things hooked into it), or the exception if it fails.
     */
    public function onCreateFileImageThumbnailSource(File $file, ?string &$imgPath, string $media): bool
    {
        // If we are on a private node, we won't do any remote calls (just as a precaution until
        // we can configure this from config.php for the private nodes)
        if (common_config('site', 'private')) {
            return true;
        }

        // All our remote Embed images lack a local filename property in the File object
        if ($file->isLocal()) {
            common_debug(sprintf('File of id==%d is local (filename: %s), so nothing Embed ' .
                'should handle.', $file->getID(), _ve($file->filename)));
            return true;
        }

        try {
            // If we have proper Embed data, there should be an entry in the File_thumbnail table.
            // If not, we're not going to do anything.
            $thumbnail = File_thumbnail::byFile($file);
        } catch (NoResultException $e) {
            // Not Embed data, or at least nothing we either can or want to use.
            common_debug('No Embed data found for file id==' . $file->getID());
            return true;
        }

        try {
            $this->storeRemoteFileThumbnail($thumbnail);
        } catch (AlreadyFulfilledException $e) {
            // aw yiss!
        } catch (Exception $e) {
            common_debug(sprintf(
                'Embed encountered an exception (%s) for file id==%d: %s',
                get_class($e),
                $file->getID(),
                _ve($e->getMessage())
            ));
            throw $e;
        }

        // Out
        $imgPath = $thumbnail->getPath();

        return !file_exists($imgPath);
    }

    public function onFileDeleteRelated(File $file, array &$related): bool
    {
        $related[] = 'File_embed';
        return true;
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

        throw new ServerException(sprintf(_('Domain not in remote thumbnail source whitelist: %s'), $host));
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
                    common_log(LOG_ERR, 'Invalid URL in Embed::getRemoteFileSize()');
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
    private function isRemoteImage($url, $headers = null)
    {
        if (empty($headers)) {
            if (!common_valid_http_url($url)) {
                common_log(LOG_ERR, 'Invalid URL in Embed::isRemoteImage()');
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
            throw new UnsupportedMediaException(_('Remote file format was not identified as an image.'), $url);
        } elseif (!$info[0] || !$info[1]) {
            throw new UnsupportedMediaException(_('Image file had impossible geometry (0 width or height)'));
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
            $fullpath = File_thumbnail::path($filename);
            // Write the file to disk. Throw Exception on failure
            if (!file_exists($fullpath)) {
                if (strpos($fullpath, INSTALLDIR) !== 0 || file_put_contents($fullpath, $imgData) === false) {
                    throw new ServerException(_('Could not write downloaded file to disk.'));
                }

                if (common_get_mime_media(MediaFile::getUploadedMimeType($fullpath)) !== 'image') {
                    @unlink($fullpath);
                    throw new UnsupportedMediaException(
                        _('Remote file format was not identified as an image.'),
                        $url
                    );
                }

                // If the image is not of the desired size, resize it
                if ($this->crop && ($info[0] > $this->thumbnail_width || $info[1] > $this->thumbnail_height)) {
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
            common_log(LOG_ERR, 'Went to write a thumbnail to disk in EmbedPlugin::storeRemoteThumbnail ' .
                "but encountered error: {$err}");
            throw $err;
        } finally {
            unset($imgData);
        }

        return [$filename, $width, $height];
    }

    /**
     * Function to create and store a thumbnail representation of a remote image
     *
     * @param $thumbnail File_thumbnail object containing the file thumbnail
     *
     * @return bool true if it succeeded, the exception if it fails, or false if it
     *              is limited by system limits (ie the file is too large.)
     */
    protected function storeRemoteFileThumbnail(File_thumbnail $thumbnail)
    {
        if (!empty($thumbnail->filename) && file_exists($thumbnail->getPath())) {
            throw new AlreadyFulfilledException(
                sprintf('A thumbnail seems to already exist for remote file with id==%u', $thumbnail->file_id)
            );
        }

        $url = $thumbnail->url; // Important not to use the getter here.

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
                'Downloading remote thumbnail for file id==%u with thumbnail URL: %s',
                $thumbnail->file_id,
                $url
            ));
            try {
                $imgData = HTTPClient::quickGet($url);
                if (isset($imgData)) {
                    list($filename, $width, $height) = $this->validateAndWriteImage(
                        $imgData,
                        $url,
                        $headers,
                        $thumbnail->file_id
                    );
                } else {
                    throw new UnsupportedMediaException('HTTPClient returned an empty result');
                }
            } catch (UnsupportedMediaException $e) {
                // Couldn't find anything that looks like an image, nothing to do
                common_debug("Embed was not able to find an image for URL `{$url}`: " . $e->getMessage());
                return false;
            }
        }

        try {
            // Update our database for the thumbnail record
            $orig                = clone $thumbnail;
            $thumbnail->filename = $filename;
            $thumbnail->width    = $width;
            $thumbnail->height   = $height;
            // Throws exception on failure.
            $thumbnail->updateWithKeys($orig);
        } catch (Exception $err) {
            common_log(LOG_ERR, 'Went to write a thumbnail entry to the database in ' .
                'EmbedPlugin::storeRemoteThumbnail but encountered error: ' . $err);
            throw $err;
        }
        return true;
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
        $versions[] = ['name' => 'Embed',
            'version'         => self::PLUGIN_VERSION,
            'author'          => 'Mikael Nordfeldth',
            'homepage'        => GNUSOCIAL_ENGINE_URL,
            'description'     => // TRANS: Plugin description.
            _m('Plugin for using and representing oEmbed, OpenGraph and other data.'), ];
        return true;
    }
}
