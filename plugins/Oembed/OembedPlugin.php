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
 * @author    hannes
 * @author    Mikael Nordfeldth
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Base class for the oEmbed plugin that does most of the heavy lifting to get
 * and display representations for remote content.
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OembedPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    // settings which can be set in config.php with addPlugin('Oembed', array('param'=>'value', ...));
    // WARNING, these are _regexps_ (slashes added later). Always escape your dots and end your strings
    public $domain_whitelist = array(       // hostname => service provider
                                    '^i\d*\.ytimg\.com$' => 'YouTube',
                                    '^i\d*\.vimeocdn\.com$' => 'Vimeo',
                                    );
    public $append_whitelist = array();  // fill this array as domain_whitelist to add more trusted sources
    public $check_whitelist  = false;    // security/abuse precaution

    protected $imgData = array();

    /**
     * Initialize the oEmbed plugin and set up the environment it needs for it.
     * Returns true if it initialized properly, the exception object if it
     * doesn't.
     */
    public function initialize()
    {
        parent::initialize();

        $this->domain_whitelist = array_merge($this->domain_whitelist, $this->append_whitelist);
    }

    /**
     * The code executed on GNU social checking the database schema, which in
     * this case is to make sure we have the plugin table we need.
     *
     * @return bool true if it ran successfully, the exception object if it doesn't.
     */
    public function onCheckSchema()
    {
        $schema = Schema::get();
        $schema->ensureTable('file_oembed', File_oembed::schemaDef());
        return true;
    }

    /**
     * This code executes when GNU social creates the page routing, and we hook
     * on this event to add our action handler for oEmbed.
     *
     * @param $m URLMapper the router that was initialized.
     * @return bool true if successful, the exception object if it isn't.
     */
    public function onRouterInitialized(URLMapper $m)
    {
        $m->connect('main/oembed', array('action' => 'oembed'));
    }

    /**
     * This event executes when GNU social encounters a remote URL we then decide
     * to interrogate for metadata.  oEmbed gloms onto it to see if we have an
     * oEmbed endpoint or image to try to represent in the post.
     *
     * @param $url string        the remote URL we're looking at
     * @param $dom DOMDocument   the document we're getting metadata from
     * @param $metadata stdClass class representing the metadata
     * @return bool true if successful, the exception object if it isn't.
     */
    public function onGetRemoteUrlMetadataFromDom($url, DOMDocument $dom, stdClass &$metadata)
    {
        try {
            common_log(LOG_INFO, 'Trying to discover an oEmbed endpoint using link headers.');
            $api = oEmbedHelper::oEmbedEndpointFromHTML($dom);
            common_log(LOG_INFO, 'Found oEmbed API endpoint ' . $api . ' for URL ' . $url);
            $params = array(
                'maxwidth' => common_config('thumbnail', 'width'),
                'maxheight' => common_config('thumbnail', 'height'),
            );
            $metadata = oEmbedHelper::getOembedFrom($api, $url, $params);

            // Facebook just gives us javascript in its oembed html,
            // so use the content of the title element instead
            if (strpos($url, 'https://www.facebook.com/') === 0) {
                $metadata->html = @$dom->getElementsByTagName('title')->item(0)->nodeValue;
            }

            // Wordpress sometimes also just gives us javascript, use og:description if it is available
            $xpath = new DomXpath($dom);
            $generatorNode = @$xpath->query('//meta[@name="generator"][1]')->item(0);
            if ($generatorNode instanceof DomElement) {
                // when wordpress only gives us javascript, the html stripped from tags
                // is the same as the title, so this helps us to identify this (common) case
                if (strpos($generatorNode->getAttribute('content'), 'WordPress') === 0
                && trim(strip_tags($metadata->html)) == trim($metadata->title)) {
                    $propertyNode = @$xpath->query('//meta[@property="og:description"][1]')->item(0);
                    if ($propertyNode instanceof DomElement) {
                        $metadata->html = $propertyNode->getAttribute('content');
                    }
                }
            }
        } catch (Exception $e) {
            // FIXME - make sure the error was because we couldn't get metadata, not something else! -mb
            common_log(LOG_INFO, 'Could not find an oEmbed endpoint using link headers, trying OpenGraph from HTML.');
            // Just ignore it!
            $metadata = OpenGraphHelper::ogFromHtml($dom);
        }

        if (isset($metadata->thumbnail_url)) {
            // sometimes sites serve the path, not the full URL, for images
            // let's "be liberal in what you accept from others"!
            // add protocol and host if the thumbnail_url starts with /
            if (substr($metadata->thumbnail_url, 0, 1) == '/') {
                $thumbnail_url_parsed = parse_url($metadata->url);
                $metadata->thumbnail_url = $thumbnail_url_parsed['scheme']."://".$thumbnail_url_parsed['host'].$metadata->thumbnail_url;
            }

            // some wordpress opengraph implementations sometimes return a white blank image
            // no need for us to save that!
            if ($metadata->thumbnail_url == 'https://s0.wp.com/i/blank.jpg') {
                unset($metadata->thumbnail_url);
            }

            // FIXME: this is also true of locally-installed wordpress so we should watch out for that.
        }
        return true;
    }

    public function onEndShowHeadElements(Action $action)
    {
        switch ($action->getActionName()) {
        case 'attachment':
            $action->element('link', array('rel'=>'alternate',
                'type'=>'application/json+oembed',
                'href'=>common_local_url(
                    'oembed',
                    array(),
                    array('format'=>'json', 'url'=>
                        common_local_url(
                            'attachment',
                            array('attachment' => $action->attachment->getID())
                        ))
                ),
                'title'=>'oEmbed'));
            $action->element('link', array('rel'=>'alternate',
                'type'=>'text/xml+oembed',
                'href'=>common_local_url(
                    'oembed',
                    array(),
                    array('format'=>'xml','url'=>
                        common_local_url(
                            'attachment',
                            array('attachment' => $action->attachment->getID())
                        ))
                ),
                'title'=>'oEmbed'));
            break;
        case 'shownotice':
            if (!$action->notice->isLocal()) {
                break;
            }
            try {
                $action->element('link', array('rel'=>'alternate',
                    'type'=>'application/json+oembed',
                    'href'=>common_local_url(
                        'oembed',
                        array(),
                        array('format'=>'json','url'=>$action->notice->getUrl())
                    ),
                    'title'=>'oEmbed'));
                $action->element('link', array('rel'=>'alternate',
                    'type'=>'text/xml+oembed',
                    'href'=>common_local_url(
                        'oembed',
                        array(),
                        array('format'=>'xml','url'=>$action->notice->getUrl())
                    ),
                    'title'=>'oEmbed'));
            } catch (InvalidUrlException $e) {
                // The notice is probably a share or similar, which don't
                // have a representational URL of their own.
            }
            break;
        }

        return true;
    }

    public function onEndShowStylesheets(Action $action)
    {
        $action->cssLink($this->path('css/oembed.css'));
        return true;
    }

    /**
     * Save embedding information for a File, if applicable.
     *
     * Normally this event is called through File::saveNew()
     *
     * @param File   $file       The newly inserted File object.
     *
     * @return boolean success
     */
    public function onEndFileSaveNew(File $file)
    {
        $fo = File_oembed::getKV('file_id', $file->getID());
        if ($fo instanceof File_oembed) {
            common_log(LOG_WARNING, "Strangely, a File_oembed object exists for new file {$file->getID()}", __FILE__);
            return true;
        }

        if (isset($file->mimetype)
            && (('text/html' === substr($file->mimetype, 0, 9)
            || 'application/xhtml+xml' === substr($file->mimetype, 0, 21)))) {
            try {
                $oembed_data = File_oembed::_getOembed($file->url);
                if ($oembed_data === false) {
                    throw new Exception('Did not get oEmbed data from URL');
                }
                $file->setTitle($oembed_data->title);
            } catch (Exception $e) {
                common_log(LOG_WARNING, sprintf(__METHOD__.': %s thrown when getting oEmbed data: %s', get_class($e), _ve($e->getMessage())));
                return true;
            }

            File_oembed::saveNew($oembed_data, $file->getID());
        }
        return true;
    }

    public function onEndShowAttachmentLink(HTMLOutputter $out, File $file)
    {
        $oembed = File_oembed::getKV('file_id', $file->getID());
        if (empty($oembed->author_name) && empty($oembed->provider)) {
            return true;
        }
        $out->elementStart('div', array('id'=>'oembed_info', 'class'=>'e-content'));
        if (!empty($oembed->author_name)) {
            $out->elementStart('div', 'fn vcard author');
            if (empty($oembed->author_url)) {
                $out->text($oembed->author_name);
            } else {
                $out->element(
                    'a',
                    array('href' => $oembed->author_url,
                                         'class' => 'url'),
                    $oembed->author_name
                );
            }
        }
        if (!empty($oembed->provider)) {
            $out->elementStart('div', 'fn vcard');
            if (empty($oembed->provider_url)) {
                $out->text($oembed->provider);
            } else {
                $out->element(
                    'a',
                    array('href' => $oembed->provider_url,
                                         'class' => 'url'),
                    $oembed->provider
                );
            }
        }
        $out->elementEnd('div');
    }

    public function onFileEnclosureMetadata(File $file, &$enclosure)
    {
        // Never treat generic HTML links as an enclosure type!
        // But if we have oEmbed info, we'll consider it golden.
        $oembed = File_oembed::getKV('file_id', $file->getID());
        if (!$oembed instanceof File_oembed || !in_array($oembed->type, array('photo', 'video'))) {
            return true;
        }

        foreach (array('mimetype', 'url', 'title', 'modified', 'width', 'height') as $key) {
            if (isset($oembed->{$key}) && !empty($oembed->{$key})) {
                $enclosure->{$key} = $oembed->{$key};
            }
        }
        return true;
    }

    public function onStartShowAttachmentRepresentation(HTMLOutputter $out, File $file)
    {
        try {
            $oembed = File_oembed::getByFile($file);
        } catch (NoResultException $e) {
            return true;
        }

        // Show thumbnail as usual if it's a photo.
        if ($oembed->type === 'photo') {
            return true;
        }

        $out->elementStart('article', ['class'=>'h-entry oembed']);
        $out->elementStart('header');
        try {
            $thumb = $file->getThumbnail(128, 128);
            $out->element('img', $thumb->getHtmlAttrs(['class'=>'u-photo oembed']));
            unset($thumb);
        } catch (Exception $e) {
            $out->element('div', ['class'=>'error'], $e->getMessage());
        }
        $out->elementStart('h5', ['class'=>'p-name oembed']);
        $out->element('a', ['class'=>'u-url', 'href'=>$file->getUrl()], common_strip_html($oembed->title));
        $out->elementEnd('h5');
        $out->elementStart('div', ['class'=>'p-author oembed']);
        if (!empty($oembed->author_name)) {
            // TRANS: text before the author name of oEmbed attachment representation
            // FIXME: The whole "By x from y" should be i18n because of different language constructions.
            $out->text(_('By '));
            $attrs = ['class'=>'h-card p-author'];
            if (!empty($oembed->author_url)) {
                $attrs['href'] = $oembed->author_url;
                $tag = 'a';
            } else {
                $tag = 'span';
            }
            $out->element($tag, $attrs, $oembed->author_name);
        }
        if (!empty($oembed->provider)) {
            // TRANS: text between the oEmbed author name and provider url
            // FIXME: The whole "By x from y" should be i18n because of different language constructions.
            $out->text(_(' from '));
            $attrs = ['class'=>'h-card'];
            if (!empty($oembed->provider_url)) {
                $attrs['href'] = $oembed->provider_url;
                $tag = 'a';
            } else {
                $tag = 'span';
            }
            $out->element($tag, $attrs, $oembed->provider);
        }
        $out->elementEnd('div');
        $out->elementEnd('header');
        $out->elementStart('div', ['class'=>'p-summary oembed']);
        $out->raw(common_purify($oembed->html));
        $out->elementEnd('div');
        $out->elementStart('footer');
        $out->elementEnd('footer');
        $out->elementEnd('article');

        return false;
    }

    public function onShowUnsupportedAttachmentRepresentation(HTMLOutputter $out, File $file)
    {
        try {
            $oembed = File_oembed::getByFile($file);
        } catch (NoResultException $e) {
            return true;
        }

        // the 'photo' type is shown through ordinary means, using StartShowAttachmentRepresentation!
        switch ($oembed->type) {
        case 'video':
        case 'link':
            if (!empty($oembed->html)
                    && (GNUsocial::isAjax() || common_config('attachments', 'show_html'))) {
                require_once INSTALLDIR.'/extlib/HTMLPurifier/HTMLPurifier.auto.php';
                $purifier = new HTMLPurifier();
                // FIXME: do we allow <object> and <embed> here? we did that when we used htmLawed, but I'm not sure anymore...
                $out->raw($purifier->purify($oembed->html));
            }
            return false;
        }

        return true;
    }

    /**
     * This event executes when GNU social is creating a file thumbnail entry in
     * the database.  We glom onto this to create proper information for oEmbed
     * object thumbnails.
     *
     * @param $file File the file of the created thumbnail
     * @param &$imgPath string = the path to the created thumbnail
     * @return bool true if it succeeds (including non-action
     * states where it isn't oEmbed data, so it doesn't mess up the event handle
     * for other things hooked into it), or the exception if it fails.
     */
    public function onCreateFileImageThumbnailSource(File $file, &$imgPath)
    {
        // If we are on a private node, we won't do any remote calls (just as a precaution until
        // we can configure this from config.php for the private nodes)
        if (common_config('site', 'private')) {
            return true;
        }

        // All our remote Oembed images lack a local filename property in the File object
        if (!is_null($file->filename)) {
            common_debug(sprintf('Filename of file id==%d is not null (%s), so nothing oEmbed should handle.', $file->getID(), _ve($file->filename)));
            return true;
        }

        try {
            // If we have proper oEmbed data, there should be an entry in the File_oembed
            // and File_thumbnail tables respectively. If not, we're not going to do anything.
            $thumbnail = File_thumbnail::byFile($file);
        } catch (NoResultException $e) {
            // Not Oembed data, or at least nothing we either can or want to use.
            common_debug('No oEmbed data found for file id=='.$file->getID());
            return true;
        }

        try {
            $this->storeRemoteFileThumbnail($thumbnail);
        } catch (AlreadyFulfilledException $e) {
            // aw yiss!
        } catch (Exception $e) {
            common_debug(sprintf('oEmbed encountered an exception (%s) for file id==%d: %s', get_class($e), $file->getID(), _ve($e->getMessage())));
            throw $e;
        }

        $imgPath = $thumbnail->getPath();

        return false;
    }

    /**
     * @return bool             false on no check made, provider name on success
     * @throws ServerException  if check is made but fails
     */
    protected function checkWhitelist($url)
    {
        if (!$this->check_whitelist) {
            return false;   // indicates "no check made"
        }

        $host = parse_url($url, PHP_URL_HOST);
        foreach ($this->domain_whitelist as $regex => $provider) {
            if (preg_match("/$regex/", $host)) {
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
     * @return string|bool the file size if it succeeds, false otherwise.
     */
    private function getRemoteFileSize($url)
    {
        try {
            if (empty($url)) {
                return false;
            }
            stream_context_set_default(array('http' => array('method' => 'HEAD')));
            $head = @get_headers($url, 1);
            if (gettype($head)=="array") {
                $head = array_change_key_case($head);
                $size = isset($head['content-length']) ? $head['content-length'] : 0;

                if (!$size) {
                    return false;
                }
            } else {
                return false;
            }
            return $size; // return formatted size
        } catch (Exception $err) {
            common_log(LOG_ERR, __CLASS__.': getRemoteFileSize on URL : '._ve($url).' threw exception: '.$err->getMessage());
            return false;
        }
    }

    /**
     * A private helper function that uses a CURL lookup to check the mime type
     * of a remote URL to see it it's an image.
     *
     * @return bool true if the remote URL is an image, or false otherwise.
     */
    private function isRemoteImage($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            common_log(LOG_ERR, "Invalid URL in OEmbed::isRemoteImage()");
            return false;
        }
        if ($url==null) {
            common_log(LOG_ERR, "URL not specified in OEmbed::isRemoteImage()");
            return false;
        }
        try {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_NOBODY, true);
            curl_exec($curl);
            $type    = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
            if (strpos($type, 'image') !== false) {
                return true;
            } else {
                return false;
            }
        } finally {
            return false;
        }
    }

    /**
     * Function to create and store a thumbnail representation of a remote image
     *
     * @param $thumbnail File_thumbnail object containing the file thumbnail
     * @return bool true if it succeeded, the exception if it fails, or false if it
     * is limited by system limits (ie the file is too large.)
     */
    protected function storeRemoteFileThumbnail(File_thumbnail $thumbnail)
    {
        if (!empty($thumbnail->filename) && file_exists($thumbnail->getPath())) {
            throw new AlreadyFulfilledException(sprintf('A thumbnail seems to already exist for remote file with id==%u', $thumbnail->file_id));
        }

        $url = $thumbnail->getUrl();
        $this->checkWhitelist($url);

        try {
            $isImage = $this->isRemoteImage($url);
            if ($isImage==true) {
                $max_size  = common_get_preferred_php_upload_limit();
                $file_size = $this->getRemoteFileSize($url);
                if (($file_size!=false) && ($file_size > $max_size)) {
                    common_debug("Went to store remote thumbnail of size " . $file_size . " but the upload limit is " . $max_size . " so we aborted.");
                    return false;
                }
            }
        } catch (Exception $err) {
            common_debug("Could not determine size of remote image, aborted local storage.");
            return $err;
        }

        // First we download the file to memory and test whether it's actually an image file
        // FIXME: To support remote video/whatever files, this needs reworking.
        common_debug(sprintf('Downloading remote thumbnail for file id==%u with thumbnail URL: %s', $thumbnail->file_id, $url));
        $imgData = HTTPClient::quickGet($url);
        $info = @getimagesizefromstring($imgData);
        if ($info === false) {
            throw new UnsupportedMediaException(_('Remote file format was not identified as an image.'), $url);
        } elseif (!$info[0] || !$info[1]) {
            throw new UnsupportedMediaException(_('Image file had impossible geometry (0 width or height)'));
        }

        $ext = File::guessMimeExtension($info['mime']);

        try {
            $filename = sprintf('oembed-%d.%s', $thumbnail->getFileId(), $ext);
            $fullpath = File_thumbnail::path($filename);
            // Write the file to disk. Throw Exception on failure
            if (!file_exists($fullpath) && file_put_contents($fullpath, $imgData) === false) {
                throw new ServerException(_('Could not write downloaded file to disk.'));
            }
        } catch (Exception $err) {
            common_log(LOG_ERROR, "Went to write a thumbnail to disk in OembedPlugin::storeRemoteThumbnail but encountered error: {$err}");
            return $err;
        } finally {
            unset($imgData);
        }

        try {
            // Updated our database for the file record
            $orig = clone($thumbnail);
            $thumbnail->filename = $filename;
            $thumbnail->width = $info[0];    // array indexes documented on php.net:
            $thumbnail->height = $info[1];   // https://php.net/manual/en/function.getimagesize.php
            // Throws exception on failure.
            $thumbnail->updateWithKeys($orig);
        } catch (exception $err) {
            common_log(LOG_ERROR, "Went to write a thumbnail entry to the database in OembedPlugin::storeRemoteThumbnail but encountered error: ".$err);
            return $err;
        }
        return true;
    }

    /**
     * Event raised when GNU social polls the plugin for information about it.
     * Adds this plugin's version information to $versions array
     *
     * @param &$versions array inherited from parent
     * @return bool true hook value
     */
    public function onPluginVersion(array &$versions)
    {
        $versions[] = array('name' => 'Oembed',
                            'version' => self::PLUGIN_VERSION,
                            'author' => 'Mikael Nordfeldth',
                            'homepage' => 'http://gnu.io/social/',
                            'description' =>
                            // TRANS: Plugin description.
                            _m('Plugin for using and representing Oembed data.'));
        return true;
    }
}
