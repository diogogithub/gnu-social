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
class Attachment_thumbnailAction extends AttachmentAction
{
    protected $thumb_w = null;  // max width
    protected $thumb_h = null;  // max height
    protected $thumb_c = null;  // crop?

    protected function doPreparation()
    {
        parent::doPreparation();

        $this->thumb_w = $this->int('w');
        $this->thumb_h = $this->int('h');
        $this->thumb_c = $this->boolean('c');
    }

    public function showPage()
    {
        // Returns a File_thumbnail object or throws exception if not available
        try {
            $file = $this->attachment->getThumbnail($this->thumb_w, $this->thumb_h, $this->thumb_c)->getFile();
        } catch (UseFileAsThumbnailException $e) {
            // With this exception, the file exists locally
            $file = $e->file;
        }

        if (!$file->isLocal()) {
            // Not locally stored, redirect to the URL the file came from
            // Don't use getURL because it can give us a local URL, which we don't want
            common_redirect($file->url, 302);
        } else {
            $filepath = $this->attachment->getPath();
            $filename = MediaFile::getDisplayName($file);

            // Disable errors, to not mess with the file contents (suppress errors in case access to this
            // function is blocked, like in some shared hosts). Automatically reset at the end of the
            // script execution, and we don't want to have any more errors until then, so don't reset it
            @ini_set('display_errors', 0);

            header("Content-Description: File Transfer");
            header("Content-Type: {$file->mimetype}");
            header("Content-Disposition: inline; filename=\"{$filename}\"");
            header('Expires: 0');
            header('Content-Transfer-Encoding: binary');
            $filesize = $this->file->size;
            // 'if available', it says, so ensure we have it
            if (empty($filesize)) {
                $filesize = filesize($this->attachment->filename);
            }
            header("Content-Length: {$filesize}");
            // header('Cache-Control: private, no-transform, no-store, must-revalidate');

            $ret = @readfile($filepath);

            if ($ret === false || $ret !== $filesize) {
                common_log(LOG_ERR, "The lengths of the file as recorded on the DB (or on disk) for the file " .
                           "{$filepath}, with id={$this->attachment->id} differ from what was sent to the user.");
            }
        }
    }
}
