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

defined('STATUSNET') || die();

require_once INSTALLDIR . '/lib/util/deletetree.php';

/**
 * Plugin install action.
 *
 * Uploads a third party plugin to the right directories.
 *
 * Takes parameters:
 *
 *    - pluginfile: plugin file
 *    - token: session token to prevent CSRF attacks
 *    - ajax: bool; whether to return Ajax or full-browser results
 *
 * Only works if the current user is logged in.
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class PlugininstallAction extends Action
{
    public $pluginfile = null;

    /**
     * @param array $args
     * @return bool
     * @throws ClientException
     */
    public function prepare(array $args = [])
    {
        parent::prepare($args);

        // @fixme these are pretty common, should a parent class factor these out?

        // Only allow POST requests

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            // TRANS: Client error displayed when trying to use another method than POST.
            // TRANS: Do not translate POST.
            $this->clientError(_('This action only accepts POST requests.'));
        }

        // CSRF protection

        $token = $this->trimmed('token');

        if (!$token || $token != common_session_token()) {
            // TRANS: Client error displayed when the session token does not match or is not given.
            $this->clientError(_m('There was a problem with your session token.'.
                ' Try again, please.'));
        }

        // Only for logged-in users

        $this->user = common_current_user();

        if (empty($this->user)) {
            // TRANS: Error message displayed when trying to perform an action that requires a logged in user.
            $this->clientError(_m('Not logged in.'));
        }

        if (!AdminPanelAction::canAdmin('plugins')) {
            // TRANS: Client error displayed when trying to enable or disable a plugin without access rights.
            $this->clientError(_m('You cannot administer plugins.'));
        }

        if (!is_writable(INSTALLDIR . '/local/plugins/') ||
            !is_writable(PUBLICDIR . '/local/plugins/')) {
            $this->clientError(_m("No permissions to write on the third party plugin upload directory(ies)."));
        }


        return true;
    }

    /**
     * Only handle if we receive an upload request
     *
     * @throws ClientException
     * @throws NoUploadedMediaException
     */
    protected function handle()
    {
        if ($this->trimmed('upload')) {
            $this->uploadPlugin();
            $url = common_local_url('pluginsadminpanel');
            common_redirect($url, 303);
        } else {
            // TRANS: Unexpected validation error on plugin upload form.
            throw new ClientException(_('Unexpected form submission.'));
        }
    }

    /**
     * Handle a plugin upload
     *
     * Does all the magic for handling a plugin upload.
     *
     * @return void
     * @throws ClientException
     * @throws NoUploadedMediaException
     */
    public function uploadPlugin(): void
    {
        // The existence of the "error" element means PHP has processed it properly even if it was ok.
        $form_name = 'pluginfile';
        if (!isset($_FILES[$form_name]) || !isset($_FILES[$form_name]['error'])) {
            throw new NoUploadedMediaException($form_name);
        }

        if ($_FILES[$form_name]['error'] != UPLOAD_ERR_OK) {
            throw new ClientException(_m('System error uploading file.'));
        }

        $filename = basename($_FILES[$form_name]['name']);
        $ext = null;
        if (preg_match('/^.+?\.([A-Za-z0-9]+)$/', $filename, $matches) === 1) {
            // we matched on a file extension, so let's see if it means something.
            $ext = mb_strtolower($matches[1]);
            $plugin_name = basename($filename, '.'.$ext);
            $temp_path = INSTALLDIR.DIRECTORY_SEPARATOR.ltrim($_FILES[$form_name]['tmp_name'], '/tmp/').'.'.$ext;
            if (!in_array($ext, ['tar', 'zip'])) { // IF not a Phar extension
                $ext = null; // Let it throw exception below
            }
        }
        if (is_null($ext)) {
            // garbage collect
            @unlink($_FILES[$form_name]['tmp_name']);
            throw new ClientException(_m('Invalid plugin package extension. Must be either tar or zip.'));
        }
        
        move_uploaded_file($_FILES[$form_name]['tmp_name'], $temp_path);
        $this->extractPlugin($temp_path, $plugin_name);
    }

    /**
     * Plugin extractor
     * The file should have the plugin_name (plus the extension) and inside two directories, the `includes` which will
     * be unpacked to local/plugins/:filename and a `public` which will go to public/local/plugins/:filename
     *
     * @param $temp_path string Current location of the plugin package (either a tarball or a zip archive)
     * @param $plugin_name string see uploadPlugin()
     * @throws ClientException If anything goes wrong
     */
    public function extractPlugin($temp_path, $plugin_name): void
    {
        $phar = new PharData($temp_path);
        $dest_installdir = INSTALLDIR . '/local/plugins/'.$plugin_name;
        $dest_publicdir = PUBLICDIR . '/local/plugins/'.$plugin_name;
        try {
            $phar->extractTo($dest_installdir, 'includes', false);
            $phar->extractTo($dest_publicdir, 'public', false);
        } catch (PharException $e) {
            // garbage collect
            @unlink($temp_path);
            // Rollback
            deleteTree($dest_installdir);
            deleteTree($dest_publicdir);
            // Warn about the failure
            throw new ClientException($e->getMessage());
        }
        foreach ([$dest_installdir.'includes',
                  $dest_publicdir.'public'] as $source) {
            $files = scandir("source");
            foreach ($files as $file) {
                if (in_array($file, ['.', '..'])) {
                    continue;
                }
                rename($source . $file, dirname($source) . $file);
            }
        }
        rmdir($dest_installdir.'includes');
        rmdir($dest_publicdir.'public');

        // garbage collect
        @unlink($temp_path);
    }
}
