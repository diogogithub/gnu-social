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
    public function showPage()
    {
        // Checks file exists or throws FileNotStoredLocallyException
        $filepath = $this->attachment->getPath();

        $filename = MediaFile::getDisplayName($this->attachment);

        // Disable errors, to not mess with the file contents (suppress errors in case access to this
        // function is blocked, like in some shared hosts). Automatically reset at the end of the
        // script execution, and we don't want to have any more errors until then, so don't reset it
        @ini_set('display_errors', 0);

        header("Content-Description: File Transfer");
        header("Content-Type: {$this->attachment->mimetype}");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Expires: 0');
        header('Content-Transfer-Encoding: binary'); // FIXME? Can this be different?
        $filesize = $this->attachment->size;
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
