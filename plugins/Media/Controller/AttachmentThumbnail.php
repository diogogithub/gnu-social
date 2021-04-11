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

namespace Plugin\Media\Controller;

/**
 * Show notice attachments
 *
 * @category  Personal
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Attachment_thumbnailAction extends Attachment_viewAction
{
    protected $thumb_w;  // max width
    protected $thumb_h;  // max height
    protected $thumb_c;  // crop?

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
     *
     * @throws ClientException
     * @throws ReflectionException
     * @throws ServerException
     */
    public function showPage(): void
    {
        // Returns a File_thumbnail object or throws exception if not available
        $filename = $this->filename;
        $filepath = $this->filepath;
        try {
            $thumbnail = $this->attachment->getThumbnail($this->thumb_w, $this->thumb_h, $this->thumb_c);
            $file      = $thumbnail->getFile();
        } catch (UseFileAsThumbnailException $e) {
            // With this exception, the file exists locally $e->file;
        } catch (FileNotFoundException $e) {
            $this->clientError(_m('No such attachment'), 404);
        } catch (Exception $e) {
            if (is_null($filepath)) {
                $this->clientError(_m('No such thumbnail'), 404);
            }
            // Remote file
        }

        // Disable errors, to not mess with the file contents (suppress errors in case access to this
        // function is blocked, like in some shared hosts). Automatically reset at the end of the
        // script execution, and we don't want to have any more errors until then, so don't reset it
        @ini_set('display_errors', 0);

        common_send_file($filepath, image_type_to_mime_type(IMAGETYPE_WEBP), $filename, 'inline');
    }
}
