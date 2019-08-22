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

/**
 * Form for deleting a plugin
 *
 * @category  Form
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class PluginDeleteForm extends PluginEnableForm
{
    /**
     * Plugin to delete
     */
    public $plugin = null;

    /**
     * Constructor
     *
     * @param HTMLOutputter $out output channel
     * @param string $plugin plugin to delete
     */
    public function __construct($out = null, $plugin = null)
    {
        parent::__construct($out);

        $this->plugin = $plugin;
    }

    /**
     * ID of the form
     *
     * @return string ID of the form
     */
    public function id()
    {
        return 'plugin-delete-' . $this->plugin;
    }

    /**
     * class of the form
     *
     * @return string of the form class
     */
    public function formClass()
    {
        return 'form_plugin_delete';
    }

    /**
     * Action of the form
     *
     * @return string URL of the action
     */
    public function action()
    {
        return common_local_url(
            'plugindelete',
            ['plugin' => $this->plugin]
        );
    }

    public function show()
    {
        if (!is_writable(INSTALLDIR . '/local/plugins/'.$this->plugin) || // We can only delete third party plugins
            PluginList::isPluginLoaded($this->plugin)) { // We can't delete a plugin that has been loaded in config.php
            return;
        }
        parent::show();
    }

    /**
     * Action elements
     *
     * @return void
     * @throws Exception
     */
    public function formActions()
    {
        // TRANS: Plugin admin panel controls
        $this->out->submit('submit', _m('plugin', 'Delete'));
    }
}
