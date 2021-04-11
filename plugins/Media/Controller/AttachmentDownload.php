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
 * Download notice attachment
 *
 * @category Personal
 * @package  GNUsocial
 *
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 *
 * @see     https:/gnu.io/social
 */
class AttachmentDownload extends Attachment
{
    public function showPage(): void
    {
        // Disable errors, to not mess with the file contents (suppress errors in case access to this
        // function is blocked, like in some shared hosts). Automatically reset at the end of the
        // script execution, and we don't want to have any more errors until then, so don't reset it
        @ini_set('display_errors', 0);

        if ($this->attachment->isLocal()) {
            try {
                $this->filepath = $this->attachment->getFileOrThumbnailPath();
            } catch (Exception $e) {
                $this->clientError(
                    _m('Requested local URL for a file that is not stored locally.'),
                    404
                );
            }
            common_send_file(
                $this->filepath,
                $this->mimetype,
                $this->filename,
                'attachment'
            );
        } else {
            common_redirect($this->attachment->getUrl(), 303);
        }
    }
}
