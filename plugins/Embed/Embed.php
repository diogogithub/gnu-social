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
 * @author    Hugo Sales <hugo@hsal.es>
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @copyright 2014-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\Embed;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\GSFile;
use App\Core\HTTPClient;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Attachment;
use App\Entity\Note;
use App\Entity\RemoteURL;
use App\Util\Common;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\ServerException;
use App\Util\Exception\TemporaryFileException;
use App\Util\Formatting;
use App\Util\TemporaryFile;
use Embed\Embed as LibEmbed;
use Exception;
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
    public function version(): string
    {
        return '3.0.0';
    }

    /**
     *  Settings which can be set in social.local.yaml
     *  WARNING, these are _regexps_ (slashes added later). Always escape your dots and end ('$') your strings
     */
    public array $domain_whitelist = [
        // hostname => service provider
        '.*' => '', // Default to allowing any host
    ];

    /**
     * This code executes when GNU social creates the page routing, and we hook
     * on this event to add our action handler for Embed.
     *
     * @param $m RouteLoader the router that was initialized.
     *
     * @throws Exception
     *
     * @return bool
     *
     */
    public function onAddRoute(RouteLoader $m): bool
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
     * @param RemoteURL $remote_url
     * @param Note      $note
     *
     * @throws DuplicateFoundException
     * @throws ServerException
     * @throws TemporaryFileException
     *
     * @return bool
     */
    public function onNewRemoteURLFromNote(RemoteURL $remote_url, Note $note): bool
    {
        // Only handle text mime
        if ($remote_url->getMimetypeMajor() !== 'text') {
            return Event::next;
        }

        // Ignore if already handled
        $attachment_embed = DB::find('attachment_embed', ['remoteurl_id' => $remote_url->getId()]);
        if (!is_null($attachment_embed)) {
            return Event::next;
        }

        $mimetype = $remote_url->getMimetype();

        if (Formatting::startsWith($mimetype, 'text/html') || Formatting::startsWith($mimetype, 'application/xhtml+xml')) {
            try {
                $embed_data                 = $this->getEmbed($remote_url->getRemoteUrl());
                $embed_data['remoteurl_id'] = $remote_url->getId();
                // Create attachment
                $embed_data['attachment_id'] = $attachment->getId();
                DB::persist(Entity\AttachmentEmbed::create($embed_data));
                DB::flush();
            } catch (Exception $e) {
                Log::warning($e);
            }
        }
        return Event::next;
    }

    /**
     * Perform an oEmbed or OpenGraph lookup for the given $url.
     *
     * Some known hosts are whitelisted with API endpoints where we
     * know they exist but autodiscovery data isn't available.
     *
     * Throws exceptions on failure.
     *
     * @param string $url
     *
     * @return array
     */
    public function getEmbed(string $url): array
    {
        Log::info('Checking for remote URL metadata for ' . $url);

        try {
            Log::info("Trying to find Embed data for {$url} with 'oscarotero/Embed'");
            $embed                     = new LibEmbed();
            $info                      = $embed->get($url);
            $metadata['title']         = $info->title;
            $metadata['description']   = $info->description;
            $metadata['author_name']   = $info->authorName;
            $metadata['author_url']    = (string) $info->authorUrl;
            $metadata['provider_name'] = $info->providerName;
            $metadata['provider_url']  = (string) $info->providerUrl;

            if (!is_null($info->image)) {
                $thumbnail_url = (string) $info->image;
            } else {
                $thumbnail_url = (string) $info->favicon;
            }

            // Check thumbnail URL validity
            $metadata['thumbnail_url'] = $thumbnail_url;
        } catch (Exception $e) {
            Log::info("Failed to find Embed data for {$url} with 'oscarotero/Embed', got exception: " . $e->getMessage());
        }

        $metadata = self::normalize($metadata);
        return $metadata;
    }

    /**
     * Normalize fetched info.
     *
     * @param array $metadata
     *
     * @return array
     */
    public static function normalize(array $metadata): array
    {
        if (isset($metadata['thumbnail_url'])) {
            // sometimes sites serve the path, not the full URL, for images
            // let's "be liberal in what you accept from others"!
            // add protocol and host if the thumbnail_url starts with /
            if ($metadata['thumbnail_url'][0] == '/') {
                $thumbnail_url_parsed      = parse_url($metadata['thumbnail_url']);
                $metadata['thumbnail_url'] = "{$thumbnail_url_parsed['scheme']}://{$thumbnail_url_parsed['host']}{$metadata['url']}";
            }

            // Some wordpress opengraph implementations sometimes return a white blank image
            // no need for us to save that!
            if ($metadata['thumbnail_url'] == 'https://s0.wp.com/i/blank.jpg') {
                $metadata['thumbnail_url'] = null;
            }
        }

        return $metadata;
    }

    /**
     * Show this attachment enhanced with the corresponding Embed data, if available
     *
     * @param array $vars
     * @param array $res
     *
     * @return bool
     */
    public function onViewAttachmentText(array $vars, array &$res): bool
    {
        $attachment = $vars['attachment'];
        try {
            $embed = Cache::get('attachment-embed-' . $attachment->getId(),
                fn () => DB::findOneBy('attachment_embed', ['attachment_id' => $attachment->getId()]));
        } catch (DuplicateFoundException $e) {
            Log::warning($e);
            return Event::next;
        } catch (NotFoundException) {
            return Event::next;
        }
        if (is_null($embed) && empty($embed->getAuthorName()) && empty($embed->getProviderName())) {
            Log::debug('Embed doesn\'t have a representation for the attachment #' . $attachment->getId());
            return Event::next;
        }

        $attributes = $embed->getImageHTMLAttributes(['class' => 'u-photo embed']);

        $res[] = Formatting::twigRenderString(<<<END
<article class="h-entry embed">
    <header>
        {% if attributes != false %}
            <img class="u-photo embed" width="{{attributes['width']}}" height="{{attributes['height']}}" src="{{attributes['src']}}" />
        {% endif %}
        <h5 class="p-name embed">
             <a class="u-url" href="{{attachment.getRemoteUrl()}}">{{embed.getTitle() | escape}}</a>
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
             {% if embed.getProviderName() is not null %}
                  <div class="fn vcard">
                      {% if embed.getProviderUrl() is null %}
                          <p>{{embed.getProviderName()}}</p>
                      {% else %}
                          <a href="{{embed.getProviderUrl()}}" class="url">{{embed.getProviderName()}}</a>
                      {% endif %}
                  </div>
             {% endif %}
        </div>
    </header>
    <div class="p-summary embed">
        {{ embed.getHtml() | escape }}
    </div>
</article>
END, ['embed' => $embed, 'attributes' => $attributes, 'attachment' => $attachment]);

        return Event::stop;
    }

    /**
     * @throws ServerException if check is made but fails
     *
     * @return bool         false on no check made, provider name on success
     * @return false|string on no check made, provider name on success
     *
     *
     */
    protected function checkWhitelist(string $url): string | bool
    {
        if ($this->check_whitelist ?? false) {
            return false;   // indicates "no check made"
        }

        $host = parse_url($url, PHP_URL_HOST);
        foreach ($this->domain_whitelist as $regex => $provider) {
            if (preg_match("/{$regex}/", $host)) {
                return $provider;    // we trust this source, return provider name
            }
        }

        throw new ServerException(_m('Domain not in remote thumbnail source whitelist: {host}', ['host' => $host]));
    }

    /**
     * Check the file size of a remote file using a HEAD request and checking
     * the content-length variable returned.  This isn't 100% foolproof but is
     * reliable enough for our purposes.
     *
     * @param string     $url
     * @param null|array $headers - if we already made a request
     *
     * @return null|int the file size if it succeeds, false otherwise.
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
     * A private helper function that uses a HEAD request to check the mimetype
     * of a remote URL to see if it's an image.
     *
     * @param mixed      $url
     * @param null|mixed $headers
     *
     * @return bool true if the remote URL is an image, or false otherwise.
     */
    private function isRemoteImage(string $url, ?array $headers = null): bool
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
            Log::error($e);
            return false;
        }
    }

    /**
     * Validate that $imgData is a valid image, place it in its folder and resize
     *
     * @param $imgData - The image data to validate
     * @param null|array $headers - The headers possible previous request to $url
     */
    protected function validateAndWriteImage($imgData, ?array $headers = null): array
    {
        $file = new TemporaryFile();
        $file->write($imgData);

        Event::handle('HashFile', [$file->getRealPath(), &$hash]);
        $filepath   = Common::config('storage', 'dir') . "embed/{$hash}" . Common::config('thumbnail', 'extension');
        $width      = Common::config('plugin_embed', 'width');
        $height     = Common::config('plugin_embed', 'height');
        $smart_crop = Common::config('plugin_embed', 'smart_crop');

        Event::handle('ResizeImagePath', [$file->getRealPath(), $filepath, &$width, &$height, $smart_crop, &$mimetype]);

        unset($file);

        if (!is_null($headers) && array_key_exists('content-disposition', $headers) && preg_match('/^.+; filename="(.+?)"$/', $headers['content-disposition'][0], $matches) === 1) {
            $original_name = $matches[1];
        }

        return [$filepath, $width, $height, $original_name ?? null, $mimetype];
    }

    /**
     * Fetch, Validate and Write a remote image from url to temporary file
     *
     * @param Attachment $attachment
     * @param string     $media_url  URL for the actual media representation
     *
     * @throws Exception
     *
     * @return array|bool
     */
    protected function fetchValidateWriteRemoteImage(Attachment $attachment, string $media_url): array | bool
    {
        if ($attachment->hasFilename() && file_exists($attachment->getPath())) {
            throw new AlreadyFulfilledException(_m('A thumbnail seems to already exist for remote file with id=={id}', ['id' => $attachment->getId()]));
        }

        if (Formatting::startsWith($media_url, 'file://')) {
            $filename = Formatting::removePrefix($media_url, 'file://');
            $info     = getimagesize($filename);
            $filename = basename($filename);
            $width    = $info[0];
            $height   = $info[1];
        } else {
            $this->checkWhitelist($media_url);
            $head    = HTTPClient::head($media_url);
            $headers = $head->getHeaders();
            $headers = array_change_key_case($headers, CASE_LOWER);

            try {
                $is_image = $this->isRemoteImage($media_url, $headers);
                if ($is_image == true) {
                    $file_size = $this->getRemoteFileSize($media_url, $headers);
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
            Log::debug('Downloading remote thumbnail for file id==' . $attachment->getId() . " with thumbnail URL: {$media_url}");

            try {
                $imgData = HTTPClient::get($media_url)->getContent();
                if (isset($imgData)) {
                    [$filepath, $width, $height, $original_name, $mimetype] = $this->validateAndWriteImage($imgData, $headers);
                } else {
                    throw new UnsupportedMediaException(_m('HTTPClient returned an empty result'));
                }
            } catch (UnsupportedMediaException $e) {
                // Couldn't find anything that looks like an image, nothing to do
                Log::debug($e);
                return false;
            }
        }

        return [$filepath, $width, $height, $original_name, $mimetype];
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
        $versions[] = [
            'name'        => 'Embed',
            'version'     => $this->version(),
            'author'      => 'Mikael Nordfeldth, Hugo Sales, Diogo Peralta Cordeiro',
            'homepage'    => GNUSOCIAL_PROJECT_URL,
            'description' => // TRANS: Plugin description.
                _m('Plugin for using and representing oEmbed, OpenGraph and other data.'),
        ];
        return Event::next;
    }
}
