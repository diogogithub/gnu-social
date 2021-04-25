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
 * OEmbed and OpenGraph implementation for GNU social
 *
 * @package   GNUsocial
 *
 * @author    Mikael Nordfeldth
 * @author    Stephen Paul Weber
 * @author    hannes
 * @author    Mikael Nordfeldth
 * @author    Miguel Dantas
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @authir    Hugo Sales <hugo@hsal.es>
 *
 * @copyright 2014-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\Embed;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\HTTPClient;
use App\Core\Log;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Attachment;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NotFoundException;
use Plugin\Embed\Entity\FileEmbed;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for the Embed plugin that does most of the heavy lifting to get
 * and display representations for remote content.
 *
 * @copyright 2014-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Embed extends Plugin
{
    /**
     *  Settings which can be set in social.local.yaml
     *  WARNING, these are _regexps_ (slashes added later). Always escape your dots and end ('$') your strings
     */
    public $domain_allowlist = [
        // hostname => service provider
        '^i\d*\.ytimg\.com$'    => 'YouTube',
        '^i\d*\.vimeocdn\.com$' => 'Vimeo',
    ];

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
    public function onAddRoute(RouteLoader $m)
    {
        $m->connect('oembed', 'main/oembed', Controller\Embed::class);
        $m->connect('embed', 'main/embed', Controller\Embed::class);
        return Event::next;
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

    /**
     * Insert oembed and opengraph tags in all HTML head elements
     */
    public function onShowHeadElements(Request $request, array $result)
    {
        $matches = [];
        preg_match(',/?([^/]+)/?.*,', $request->getPathInfo(), $matches);
        switch ($matches[1]) {
        case 'attachment':
            $url = "{$matches[1]}/{$matches[2]}";
            break;
        }

        if (isset($url)) {
            foreach (['xml', 'json'] as $format) {
                $result[] = [
                    'link' => [
                        'rel'   => 'alternate',
                        'type'  => "application/{$format}+oembed",
                        'href'  => Router::url('embed', ['format' => $format, 'url' => $url]),
                        'title' => 'oEmbed',
                    ], ];
            }
        }
        return Event::next;
    }

    /**
     * Save embedding information for an Attachment, if applicable.
     *
     * Normally this event is called through File::saveNew()
     *
     * @param Attachment $attachment The newly inserted Attachment object.
     *
     * @return bool success
     */
    public function onAttachmentStoreNew(Attachment $attachment)
    {
        try {
            DB::findOneBy('attachment_embed', ['attachment_id' => $attachment->getId()]);
        } catch (NotFoundException) {
        } catch (DuplicateFoundException) {
            Log::warning("Strangely, an attachment_embed object exists for new file {$attachment->getID()}");
            return Event::next;
        }

        if (!is_null($attachment->getRemoteUrl()) || (!is_null($mimetype = $attachment->getMimetype()) && (('text/html' === substr($mimetype, 0, 9) || 'application/xhtml+xml' === substr($mimetype, 0, 21))))) {
            try {
                $embed_data = EmbedHelper::getEmbed($attachment->getRemoteUrl());
                dd($embed_data);
                if ($embed_data === false) {
                    throw new Exception("Did not get Embed data from URL {$attachment->url}");
                }
                $attachment->setTitle($embed_data['title']);
            } catch (Exception $e) {
                Log::warning($e);
                return true;
            }

            FileEmbed::saveNew($embed_data, $attachment->getId());
        }
        return true;
    }

    /**
     * Replace enclosure representation of an attachment with the data from embed
     *
     * @param mixed $enclosure
     */
    public function onFileEnclosureMetadata(Attachment $attachment, &$enclosure)
    {
        // Never treat generic HTML links as an enclosure type!
        // But if we have embed info, we'll consider it golden.
        try {
            $embed = DB::findOneBy('attachment_embed', ['attachment_id' => $attachment->getId()]);
        } catch (NotFoundException) {
            return Event::next;
        }

        foreach (['mimetype', 'url', 'title', 'modified', 'width', 'height'] as $key) {
            if (isset($embed->{$key}) && !empty($embed->{$key})) {
                $enclosure->{$key} = $embed->{$key};
            }
        }
        return true;
    }

    /** Placeholder */
    public function onShowAttachment(Attachment $attachment, array &$res)
    {
        try {
            $embed = Cache::get('attachment-embed-' . $attachment->getId(),
                                fn () => DB::findOneBy('attachment_embed', ['attachment_id' => $attachment->getId()]));
        } catch (DuplicateFoundException $e) {
            Log::waring($e);
            return Event::next;
        } catch (NotFoundException) {
            return Event::next;
        }
        if (is_null($embed) && empty($embed->getAuthorName()) && empty($embed->getProvider())) {
            return Event::next;
        }

        $thumbnail  = AttachmentThumbnail::getOrCreate(attachment: $attachment, width: $width, height: $height, crop: $smart_crop);
        $attributes = $thumbnail->getHTMLAttributes(['class' => 'u-photo embed']);

        $res[] = Formatting::twigRender(<<<END
<article class="h-entry embed">
    <header>
        <img class="u-photo embed" width="{{attributes['width']}}" height="{{attributes['height']}}" src="{{attributes['src']}}" />
        <h5 class="p-name embed">
             <a class="u-url" href="{{attachment.getUrl()}}">{{embed.getTitle() | escape}}</a>
        </h5>
        <div class="p-author embed">
             {% if embed.getAuthorName() is not null %}
                  <div class="fn vcard author">
                      {% if embed.getAuthorUrl() is null %}
                           <p>{{embed.getAuthorName()}}</p>
                      {% else %}
                           <a href="{{embed.getAuthorUrl()}}" class="url">{{embed.getAuthorName()}}</a>
                      {% endif %}
                  </div>
             {% endif %}
             {% if embed.getProvider() is not null %}
                  <div class="fn vcard">
                      {% if embed.getProviderUrl() is null %}
                          <p>{{embed.getProvider()}}</p>
                      {% else %}
                          <a href="{{embed.getProviderUrl()}}" class="url">{{embed.getProvider()}}</a>
                      {% endif %}
                  </div>
             {% endif %}
        </div>
    </header>
    <div class="p-summary embed">
        {{ embed.getHtml() | escape }}
    </div>
</article>
END, ['embed' => $embed, 'thumbnail' => $thumbnail, 'attributes' => $attributes]);

        return Event::stop;
    }

    /**
     * @throws ServerException if check is made but fails
     *
     * @return bool false on no check made, provider name on success
     */
    protected function checkAllowlist(string $url)
    {
        if (!$this->check_allowlist) {
            return false;   // indicates "no check made"
        }

        $host = parse_url($url, PHP_URL_HOST);
        foreach ($this->domain_allowlist as $regex => $provider) {
            if (preg_match("/{$regex}/", $host)) {
                return $provider;    // we trust this source, return provider name
            }
        }

        throw new ServerException(_m('Domain not in remote thumbnail source allowlist: {host}', ['host' => $host]));
    }

    /**
     * Check the file size of a remote file using a HEAD request and checking
     * the content-length variable returned.  This isn't 100% foolproof but is
     * reliable enough for our purposes.
     *
     * @param string $url
     * @param array  $headers - if we already made a request
     *
     * @return bool|string the file size if it succeeds, false otherwise.
     */
    private function getRemoteFileSize(string $url, ?array $headers = null): ?int
    {
        try {
            if ($headers === null) {
                if (!Common::isValidHttpUrl($url)) {
                    Log::error('Invalid URL in Embed::getRemoteFileSize()');
                    return false;
                }
                $head    = HTTPClient::head($url);
                $headers = $head->getHeaders();
                $headers = array_change_key_case($headers, CASE_LOWER);
            }
            return $headers['content-length'] ?? false;
        } catch (Exception $e) {
            Loog::error($e);
            return false;
        }
    }

    /**
     * A private helper function that uses a HEAD request to check the mime type
     * of a remote URL to see it it's an image.
     *
     * @param mixed      $url
     * @param null|mixed $headers
     *
     * @return bool true if the remote URL is an image, or false otherwise.
     */
    private function isRemoteImage(string $url, ?array $headers = null): ?int
    {
        try {
            if ($headers === null) {
                if (!Common::isValidHttpUrl($url)) {
                    Log::error('Invalid URL in Embed::getRemoteFileSize()');
                    return false;
                }
                $head    = HTTPClient::head($url);
                $headers = $head->getHeaders();
                $headers = array_change_key_case($headers, CASE_LOWER);
            }
            return !empty($headers['content-type']) && GSFile::mimetypeMajor($headers['content-type']) === 'image';
        } catch (Exception $e) {
            Loog::error($e);
            return false;
        }
    }

    /**
     * Validate that $imgData is a valid image, place it in it's folder and resize
     *
     * @param $imgData - The image data to validate
     * @param null|string $url     - The url where the image came from, to fetch metadata
     * @param null|array  $headers - The headers possible previous request to $url
     */
    protected function validateAndWriteImage($imgData, string $url, array $headers): array
    {
        $file = new TemporaryFile();
        $file->write($imgData);

        if (array_key_exists('content-disposition', $headers) && preg_match('/^.+; filename="(.+?)"$/', $headers['content-disposition'], $matches) === 1) {
            $original_name = $matches[1];
        }

        $mimetype = $headers['content-type'];
        Event::handle('AttachmentValidation', [$file, &$mimetype]);

        $hash     = hash_file(Attachment::FILEHASH_ALGO, $file->getPathname());
        $filename = Common::config('attachments', 'dir') . "embed/{$hash}";
        $file->commit($filename);
        unset($file);

        return [$filename, $width, $height, $original_name, $mimetype];
    }

    /**
     * Function to create and store a thumbnail representation of a remote image
     *
     * @param $thumbnail FileThumbnail object containing the file thumbnail
     *
     * @return bool true if it succeeded, the exception if it fails, or false if it
     *              is limited by system limits (ie the file is too large.)
     */
    protected function storeRemoteThumbnail(Attachment $attachment): bool
    {
        $path = $attachment->getPath();
        if (file_exists($path)) {
            throw new AlreadyFulfilledException(_m('A thumbnail seems to already exist for remote file with id=={id}', ['id' => $attachment->id]));
        }

        $url = $attachment->getRemoteUrl();

        if (substr($url, 0, 7) == 'file://') {
            $filename = substr($url, 7);
            $info     = getimagesize($filename);
            $filename = basename($filename);
            $width    = $info[0];
            $height   = $info[1];
        } else {
            $this->checkAllowlist($url);
            $head    = HTTPClient::head($url);
            $headers = $head->getHeaders();
            $headers = array_change_key_case($headers, CASE_LOWER);

            try {
                $is_image = $this->isRemoteImage($url, $headers);
                if ($is_image == true) {
                    $file_size = $this->getRemoteFileSize($url, $headers);
                    $max_size  = Common::config('attachments', 'file_quota');
                    if (($file_size != false) && ($file_size > $max_size)) {
                        Log::debug("Wanted to store remote thumbnail of size {$file_size} but the upload limit is {$max_size} so we aborted.");
                        return false;
                    }
                } else {
                    return false;
                }
            } catch (Exception $err) {
                Log::debug('Could not determine size of remote image, aborted local storage.');
                throw $err;
            }

            // First we download the file to memory and test whether it's actually an image file
            Log::debug("Downloading remote thumbnail for file id=={$attachment->id} with thumbnail URL: {$url}");
            try {
                $imgData = HTTPClient::get($url);
                if (isset($imgData)) {
                    [$filename, $width, $height, $original_name, $mimetype] = $this->validateAndWriteImage($imgData, $url, $headers);
                } else {
                    throw new UnsupportedMediaException(_m('HTTPClient returned an empty result'));
                }
            } catch (UnsupportedMediaException $e) {
                // Couldn't find anything that looks like an image, nothing to do
                Log::debug($e);
                return false;
            }
        }

        DB::persist(AttachmentThumbnail::create(['attachment_id' => $attachment->id, 'width' => $width, 'height' => $height]));
        $attachment->setFilename($filename);
        DB::flush();

        return true;
    }
}
