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

require INSTALLDIR . "/lib/pluginenableform.php";
require INSTALLDIR . "/lib/plugindisableform.php";

/**
 * Plugin list
 *
 * @category Admin
 * @package  GNUsocial
 * @author   Brion Vibber <brion@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class PluginList extends Widget
{
    public $plugins = [];

    public function __construct($plugins, $out)
    {
        parent::__construct($out);
        $this->plugins = $plugins;
    }

    public function show()
    {
        $this->startList();
        $this->showPlugins();
        $this->endList();
    }

    public function startList()
    {
        $this->out->elementStart('table', 'plugin_list');
    }

    public function endList()
    {
        $this->out->elementEnd('table');
    }

    public function showPlugins()
    {
        foreach ($this->plugins as $plugin) {
            $pli = $this->newListItem($plugin);
            $pli->show();
        }
    }

    public function newListItem($plugin)
    {
        return new PluginListItem($plugin, $this->out);
    }
}

class PluginListItem extends Widget
{
    /** Current plugin. */
    public $plugin = null;

    /** Local cache for plugin version info */
    protected static $versions = false;

    public function __construct($plugin, $out)
    {
        parent::__construct($out);
        $this->plugin = $plugin;
    }

    public function show()
    {
        $meta = $this->metaInfo();

        $this->out->elementStart('tr', array('id' => 'plugin-' . $this->plugin));

        // Name and controls
        $this->out->elementStart('td');
        $this->out->elementStart('div');
        if (!empty($meta['homepage'])) {
            $this->out->elementStart('a', array('href' => $meta['homepage']));
        }
        $this->out->text($this->plugin);
        if (!empty($meta['homepage'])) {
            $this->out->elementEnd('a');
        }
        $this->out->elementEnd('div');

        $form = $this->getControlForm();
        $form->show();

        $this->out->elementEnd('td');

        // Version and authors
        $this->out->elementStart('td');
        if (!empty($meta['version'])) {
            $this->out->elementStart('div');
            $this->out->text($meta['version']);
            $this->out->elementEnd('div');
        }
        if (!empty($meta['author'])) {
            $this->out->elementStart('div');
            $this->out->text($meta['author']);
            $this->out->elementEnd('div');
        }
        $this->out->elementEnd('td');

        // Description
        $this->out->elementStart('td');
        if (!empty($meta['rawdescription'])) {
            $this->out->raw($meta['rawdescription']);
        } elseif (!empty($meta['description'])) {
            $this->out->raw($meta['description']);
        }
        $this->out->elementEnd('td');

        $this->out->elementEnd('tr');
    }

    /**
     * Pull up the appropriate control form for this plugin, depending
     * on its current state.
     *
     * @return Form
     */
    protected function getControlForm()
    {
        $key = 'disable-' . $this->plugin;
        if (common_config('plugins', $key)) {
            return new PluginEnableForm($this->out, $this->plugin);
        } else {
            return new PluginDisableForm($this->out, $this->plugin);
        }
    }

    /**
     * Grab metadata about this plugin...
     * Warning: horribly inefficient and may explode!
     * Doesn't work for disabled plugins either.
     *
     * @fixme pull structured data from plugin source
     */
    public function metaInfo()
    {
        $versions = self::getPluginVersions();
        $found = false;

        foreach ($versions as $info) {
            // We need a proper name for comparison, that is, without spaces nor the `(section)`
            // Therefore, a plugin named "Diogo Cordeiro (diogo@fc.up.pt)" becomes "DiogoCordeiro"
            $name_without_spaces = str_replace(' ', '', $info['name']);
            $name_without_spaces_nor_parentheses_section = substr($name_without_spaces, 0, strpos($name_without_spaces.'(', '('));

            if (strtolower($name_without_spaces_nor_parentheses_section) == strtolower($this->plugin)) {
                if ($found) {
                    $found['rawdescription'] .= "<br />\n" . $info['rawdescription'];
                } else {
                    $found = $info;
                }
            }
        }

        if ($found) {
            return $found;
        } else {
            return ['name' => $this->plugin,
                // TRANS: Plugin description for a disabled plugin.
                'rawdescription' => _m('plugin-description', '(The plugin description is unavailable when a plugin has been disabled.)')];
        }
    }

    /**
     * Lazy-load the set of active plugin version info
     * @return array
     */
    protected static function getPluginVersions()
    {
        if (!is_array(self::$versions)) {
            $versions = [];
            Event::handle('ModuleVersion', [&$versions]);
            self::$versions = $versions;
        }
        return self::$versions;
    }
}
