<?php

if (!defined('GNUSOCIAL')) { exit(1); }

/**
 * Download notice attachment
 *
 * @category Personal
 * @package  GNUsocial
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     https:/gnu.io/social
 */
class Attachment_downloadAction extends AttachmentAction
{
    public function showPage(): void
    {
        // Disable errors, to not mess with the file contents (suppress errors in case access to this
        // function is blocked, like in some shared hosts). Automatically reset at the end of the
        // script execution, and we don't want to have any more errors until then, so don't reset it
        @ini_set('display_errors', 0);

        common_send_file($this->filepath, $this->mimetype, $this->filename, 'attachment');
    }
}
