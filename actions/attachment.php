<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Show notice attachments
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Personal
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('GNUSOCIAL')) { exit(1); }

/**
 * Show notice attachments
 *
 * @category Personal
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class AttachmentAction extends ManagedAction
{
    /**
     * Attachment File object to show
     */
    var $attachment = null;

    /**
     * Load attributes based on database arguments
     *
     * Loads all the DB stuff
     *
     * @param array $args $_REQUEST array
     *
     * @return success flag
     */

    protected function prepare(array $args=array())
    {
        parent::prepare($args);

        try {
            if (!empty($id = $this->trimmed('attachment'))) {
                $this->attachment = File::getByID($id);
            } elseif (!empty($filehash = $this->trimmed('filehash'))) {
                $this->attachment = File::getByHash($filehash);
            }
        } catch (Exception $e) {
            // Not found
        }
        if (!$this->attachment instanceof File) {
            // TRANS: Client error displayed trying to get a non-existing attachment.
            $this->clientError(_('No such attachment.'), 404);
        }

        $filename = $this->attachment->getFileOrThumbnailPath();

        if (empty($filename)) {
            $this->clientError(_('Requested local URL for a file that is not stored locally.'), 404);
        }
        return true;
    }

    /**
     * Is this action read-only?
     *
     * @return boolean true
     */
    function isReadOnly($args)
    {
        return true;
    }

    /**
     * Title of the page
     *
     * @return string title of the page
     */
    function title()
    {
        $a = new Attachment($this->attachment);
        return $a->title();
    }

    public function showPage()
    {
        if (empty($this->attachment->getFileOrThumbnailPath())) {
            // if it's not a local file, gtfo
            common_redirect($this->attachment->getUrl(), 303);
        }

        parent::showPage();
    }

    /**
     * Fill the content area of the page
     *
     * Shows a single notice list item.
     *
     * @return void
     */
    function showContent()
    {
        $ali = new Attachment($this->attachment, $this);
        $cnt = $ali->show();
    }

    /**
     * Don't show page notice
     *
     * @return void
     */
    function showPageNoticeBlock()
    {
    }

    /**
     * Show aside: this attachments appears in what notices
     *
     * @return void
     */
    function showSections() {
        $ns = new AttachmentNoticeSection($this);
        $ns->show();
    }

    /**
     * Last-modified date for file
     *
     * @return int last-modified date as unix timestamp
     */
    public function lastModified()
    {
        if (common_config('site', 'use_x_sendfile')) {
            return null;
        }
        $path = $this->attachment->getFileOrThumbnailPath();
        if (!empty($path)) {
            return filemtime($path);
        } else {
            return null;
        }
    }

    /**
     * etag header for file
     *
     * This returns the same data (inode, size, mtime) as Apache would,
     * but in decimal instead of hex.
     *
     * @return string etag http header
     */
    function etag()
    {
        if (common_config('site', 'use_x_sendfile')) {

            return null;
        }

        $path = $this->attachment->getFileOrThumbnailPath();

        $cache = Cache::instance();
        if($cache) {
            if (empty($path)) {
                return null;
            }
            $key = Cache::key('attachments:etag:' . $path);
            $etag = $cache->get($key);
            if($etag === false) {
                $etag = crc32(file_get_contents($path));
                $cache->set($key,$etag);
            }
            return $etag;
        }

        if (!empty($path)) {
            $stat = stat($path);
            return '"' . $stat['ino'] . '-' . $stat['size'] . '-' . $stat['mtime'] . '"';
        } else {
            return null;
        }
    }

    /**
     * Include $filepath in the response, for viewing and downloading.
     * If provided, $filesize is used to size the HTTP request,
     * otherwise it's value is calculated
     */
    static function sendFile(string $filepath, $filesize) {
        if (is_string(common_config('site', 'x-static-delivery'))) {
            $tmp = explode(INSTALLDIR, $filepath);
            $relative_path = end($tmp);
            common_debug("Using Static Delivery with header: '" .
                         common_config('site', 'x-static-delivery') . ": {$relative_path}'");
            header(common_config('site', 'x-static-delivery') . ": {$relative_path}");
        } else {
            if (empty($filesize)) {
                $filesize = filesize($filepath);
            }
            header("Content-Length: {$filesize}");
            // header('Cache-Control: private, no-transform, no-store, must-revalidate');

            $ret = @readfile($filepath);

            if ($ret === false) {
                common_log(LOG_ERR, "Couldn't read file at {$filepath}.");
            } elseif ($ret !== $filesize) {
                common_log(LOG_ERR, "The lengths of the file as recorded on the DB (or on disk) for the file " .
                           "{$filepath} differ from what was sent to the user ({$filesize} vs {$ret}).");
            }
        }
    }
}
