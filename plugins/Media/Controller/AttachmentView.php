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
 * View notice attachment
 *
 * @package  GNUsocial
 *
 * @author   Miguel Dantas <biodantasgs@gmail.com>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 */
class AttachmentView extends Attachment
{
    public function showPage(): void
    {
        // Disable errors, to not mess with the file contents (suppress errors in case access to this
        // function is blocked, like in some shared hosts). Automatically reset at the end of the
        // script execution, and we don't want to have any more errors until then, so don't reset it
        @ini_set('display_errors', 0);

        if ($this->attachment->isLocal() || $this->attachment->isFetchedRemoteFile()) {
            try {
                $this->filepath = $this->attachment->getFileOrThumbnailPath();
            } catch (Exception $e) {
                $this->clientError(
                    _m('Requested local URL for a file that is not stored locally.'),
                    404
                );
            }
            $disposition = 'attachment';
            if (in_array(common_get_mime_media($this->mimetype), ['image', 'video'])) {
                $disposition = 'inline';
            }
            common_send_file($this->filepath, $this->mimetype, $this->filename, $disposition);
        } else {
            common_redirect($this->attachment->getUrl(), 303);
        }
    }
}
