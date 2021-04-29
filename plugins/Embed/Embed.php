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
use App\Core\GSFile;
use App\Core\HTTPClient;
use App\Core\Log;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Core\Security;
use App\Entity\Attachment;
use App\Entity\AttachmentThumbnail;
use App\Util\Common;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NotFoundException;
use App\Util\Formatting;
use App\Util\TemporaryFile;
use Embed\Embed as LibEmbed;
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
        '.*' => '', // Default to allowing any host
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
     * Insert oembed and opengraph tags in all HTML head elements
     */
    public function onShowHeadElements(Request $request, array &$result)
    {
        $matches = [];
        preg_match(',/?([^/]+)/?(.*),', $request->getPathInfo(), $matches);
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

        if ($attachment->hasRemoteUrl() && $attachment->hasMimetype()) {
            $mimetype = $attachment->getMimetype();
            if (Formatting::startsWith($mimetype, 'text/html') || Formatting::startsWith($mimetype, 'application/xhtml+xml')) {
                try {
                    $embed_data                  = $this->getEmbed($attachment->getRemoteUrl(), $attachment);
                    $embed_data['attachment_id'] = $attachment->getId();
                    DB::persist(Entity\AttachmentEmbed::create($embed_data));
                    DB::flush();
                } catch (Exception $e) {
                    Log::warning($e);
                    return Event::next;
                }
            }
        }
        return Event::next;
    }

    /**
     * Replace enclosure representation of an attachment with the data from embed
     */
    public function onAttachmentFileInfo(int $attachment_id, ?array &$enclosure)
    {
        try {
            $embed = DB::findOneBy('attachment_embed', ['attachment_id' => $attachment_id]);
        } catch (NotFoundException) {
            return Event::next;
        }

        // We know about this attachment, so we 'own' it, but know
        // that it doesn't have an image
        if (!$embed->isImage()) {
            $enclosure = null;
            return Event::stop;
        }

        $enclosure = [
            'filepath' => $embed->getFilepath(),
            'mimetype' => $embed->getMimetype(),
            'title'    => $embed->getTitle(),
            'width'    => $embed->getWidth(),
            'height'   => $embed->getHeight(),
            'url'      => $embed->getUrl(),
        ];

        return Event::stop;
    }

    /**
     * Show this attachment enhanced with the corresponing Embed data, if available
     */
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
            Log::debug('Embed doesn\'t have a representation for the attachment #' . $attachment->getId());
            return Event::next;
        }

        $width      = Common::config('thumbnail', 'width');
        $height     = Common::config('thumbnail', 'height');
        $smart_crop = Common::config('thumbnail', 'smart_crop');
        $attributes = $embed->getImageHTMLAttributes(['class' => 'u-photo embed']);

        $res[] = Formatting::twigRender(<<<END
<article class="h-entry embed">
    <header>
        {% if attributes != false %}
            <img class="u-photo embed" width="{{attributes['width']}}" height="{{attributes['height']}}" src="{{attributes['src']}}" />
        {% endif %}
        <h5 class="p-name embed">
             <a class="u-url" href="{{embed.getUrl()}}">{{embed.getTitle() | escape}}</a>
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
END, ['embed' => $embed, 'attributes' => $attributes]);

        return Event::stop;
    }

    /**
     * @throws ServerException if check is made but fails
     *
     * @return bool false on no check made, provider name on success
     */
    protected function checkAllowlist(string $url)
    {
        if ($this->check_allowlist ?? false) {
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
            return $headers['content-length'][0] ?? false;
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
            return !empty($headers['content-type']) && GSFile::mimetypeMajor($headers['content-type'][0]) === 'image';
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

        Event::handle('HashFile', [$file->getRealPath(), &$hash]);
        $filepath   = Common::config('storage', 'dir') . "embed/{$hash}" . Common::config('thumbnail', 'extension');
        $width      = Common::config('thumbnail', 'width');
        $height     = Common::config('thumbnail', 'height');
        $smart_crop = Common::config('thumbnail', 'smart_crop');
        Event::handle('ResizeImagePath', [$file->getRealPath(), $filepath, $width, $height, $smart_crop, &$mimetype]);

        unset($file);

        if (array_key_exists('content-disposition', $headers) && preg_match('/^.+; filename="(.+?)"$/', $headers['content-disposition'][0], $matches) === 1) {
            $original_name = $matches[1];
        }

        return [$filepath, $width, $height, $original_name ?? null, $mimetype];
    }

    /**
     * Create and store a thumbnail representation of a remote image
     */
    protected function storeRemoteThumbnail(Attachment $attachment): array | bool
    {
        if ($attachment->hasFilename() && file_exists($attachment->getPath())) {
            throw new AlreadyFulfilledException(_m('A thumbnail seems to already exist for remote file with id=={id}', ['id' => $attachment->getId()]));
        }

        $url = $attachment->getRemoteUrl();

        if (Formatting::startsWith($url, 'file://')) {
            $filename = Formatting::removePrefix($url, 'file://');
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
                        throw new \Exception("Wanted to store remote thumbnail of size {$file_size} but the upload limit is {$max_size} so we aborted.");
                    }
                } else {
                    return false;
                }
            } catch (Exception $err) {
                Log::debug('Could not determine size of remote image, aborted local storage.');
                throw $err;
            }

            // First we download the file to memory and test whether it's actually an image file
            Log::debug('Downloading remote thumbnail for file id==' . $attachment->getId() . " with thumbnail URL: {$url}");
            try {
                $imgData = HTTPClient::get($url)->getContent();
                if (isset($imgData)) {
                    [$filepath, $width, $height, $original_name, $mimetype] = $this->validateAndWriteImage($imgData, $url, $headers);
                } else {
                    throw new UnsupportedMediaException(_m('HTTPClient returned an empty result'));
                }
            } catch (UnsupportedMediaException $e) {
                // Couldn't find anything that looks like an image, nothing to do
                Log::debug($e);
                return false;
            }
        }

        DB::persist(AttachmentThumbnail::create(['attachment_id' => $attachment->getId(), 'width' => $width, 'height' => $height]));
        DB::flush();

        return [$filepath, $width, $height, $original_name, $mimetype];
    }

    /**
     * Perform an oEmbed or OpenGraph lookup for the given $url.
     *
     * Some known hosts are allowlisted with API endpoints where we
     * know they exist but autodiscovery data isn't available.
     *
     * Throws exceptions on failure.
     *
     * @param string $url
     *
     * @throws EmbedHelper_BadHtmlException
     * @throws HTTP_Request2_Exception
     *
     * @return object
     */
    public function getEmbed(string $url, Attachment $attachment): array
    {
        Log::info('Checking for remote URL metadata for ' . $url);

        try {
            Log::info("Trying to find Embed data for {$url} with 'oscarotero/Embed'");
            $embed                     = new LibEmbed();
            $info                      = $embed->get($url);
            $metadata['title']         = $info->title;
            $metadata['html']          = Security::sanitize($info->description);
            $metadata['url']           = $info->url;
            $metadata['author_name']   = $info->authorName;
            $metadata['author_url']    = $info->authorUrl;
            $metadata['provider_name'] = $info->providerName;
            $metadata['provider_url']  = $info->providerUrl;

            if (!is_null($info->image)) {
                if (Formatting::startsWith($info->image, 'data')) {
                    // Inline image
                    $imgData                                                = base64_decode(substr($info->image, stripos($info->image, 'base64,') + 7));
                    [$filepath, $width, $height, $original_name, $mimetype] = $this->validateAndWriteImage($imgData);
                } else {
                    $attachment->setRemoteUrl((string) $info->image);
                    [$filepath, $width, $height, $original_name, $mimetype] = $this->storeRemoteThumbnail($attachment);
                }
                $metadata['width']    = $width;
                $metadata['height']   = $height;
                $metadata['mimetype'] = $mimetype;
                $metadata['filename'] = Formatting::removePrefix($filepath, Common::config('storage', 'dir'));
            }
        } catch (Exception $e) {
            Log::info("Failed to find Embed data for {$url} with 'oscarotero/Embed', got exception: " . get_class($e));
        }

        $metadata = self::normalize($metadata);
        $attachment->setTitle($metadata['title']);
        return $metadata;
    }

    /**
     * Normalize fetched info.
     */
    public static function normalize(array $data): array
    {
        if (isset($metadata['url'])) {
            // sometimes sites serve the path, not the full URL, for images
            // let's "be liberal in what you accept from others"!
            // add protocol and host if the thumbnail_url starts with /
            if ($metadata['url'][0] == '/') {
                $thumbnail_url_parsed = parse_url($metadata['url']);
                $metadata['url']      = "{$thumbnail_url_parsed['scheme']}://{$thumbnail_url_parsed['host']}{$metadata['url']}";
            }

            // Some wordpress opengraph implementations sometimes return a white blank image
            // no need for us to save that!
            if ($metadata['url'] == 'https://s0.wp.com/i/blank.jpg') {
                $metadata['url'] = null;
            }

            if (!isset($data['width'])) {
                $data['width']  = Common::config('thumbnail', 'width');
                $data['height'] = Common::config('thumbnail', 'height');
            }
        }

        return $data;
    }
}
