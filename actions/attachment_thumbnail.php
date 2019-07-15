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

    /**
     * Show an inline representation of an attachment of the size
     * requested in the GET variables (read in the constructor). Tries
     * to send the most appropriate file with the correct size and
     * headers or displays an error if it's not possible.
     */
    public function showPage()
    {

        // Returns a File_thumbnail object or throws exception if not available
        try {
            $thumbnail = $this->attachment->getThumbnail($this->thumb_w, $this->thumb_h, $this->thumb_c);
            $file = $thumbnail->getFile();
        } catch (UseFileAsThumbnailException $e) {
            // With this exception, the file exists locally
            $file = $e->file;
        } catch(FileNotFoundException $e) {
            $this->clientError(_('No such attachment'), 404);
        }

        // Checks file exists or throws FileNotFoundException
        $filepath = $file->getFileOrThumbnailPath($thumbnail);
        $filesize = $this->attachment->getFileOrThumbnailSize($thumbnail);
        $mimetype = $file->getFileOrThumbnailMimetype($thumbnail);
        $filename = MediaFile::getDisplayName($file);

        // Disable errors, to not mess with the file contents (suppress errors in case access to this
        // function is blocked, like in some shared hosts). Automatically reset at the end of the
        // script execution, and we don't want to have any more errors until then, so don't reset it
        @ini_set('display_errors', 0);

        header("Content-Description: File Transfer");
        header("Content-Type: {$mimetype}");
        header("Content-Disposition: inline; filename=\"{$filename}\"");
        header('Expires: 0');
        header('Content-Transfer-Encoding: binary');

        AttachmentAction::sendFile($filepath, $filesize);
    }
}
