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

    /**
     * PluginList constructor.
     * @param Action $out
     * @param array|null $plugins
     */
    public function __construct(Action $out, ?array $plugins = null)
    {
        parent::__construct($out);
        $this->plugins = is_null($plugins) ? $this->grabAllPluginNames() : $plugins;
    }

    /**
     * List of names of all available plugins (distribution and third parties).
     * Warning: Plugin not modules, it doesn't include core modules.
     *
     * @return array
     */
    public static function grabAllPluginNames(): array
    {
        $plugins = [];
        $distribution_plugins = glob(INSTALLDIR . '/plugins/*');
        foreach ($distribution_plugins as $plugin) {
            $plugin_name = ltrim($plugin, INSTALLDIR . '/plugins/');
            if ($plugin_name == 'README.md') {
                continue;
            }
            $plugins[] = $plugin_name;
        }
        unset($distribution_plugins);
        $thirdparty_plugins = glob(INSTALLDIR . '/local/plugins/*');
        foreach ($thirdparty_plugins as $plugin) {
            $plugins[] = ltrim($plugin, INSTALLDIR . '/local/plugins/');
        }
        unset($thirdparty_plugins);
        natsort($plugins);
        return $plugins;
    }

    public function show()
    {
        if (!$this->plugins) {
            $this->out->element('p', null,
                // TRANS: Text displayed on plugin admin page when no plugin are enabled.
                _m('All plugins have been disabled from the ' .
                    'site\'s configuration file.'));
        }
        $this->startList();
        $this->showPlugins();
        $this->endList();
    }

    public function startList(): void
    {
        $this->out->elementStart('table', 'plugin_list');
    }

    public function endList(): void
    {
        $this->out->elementEnd('table');
    }

    public function showPlugins(): void
    {
        foreach ($this->plugins as $plugin) {
            $pli = $this->newListItem($plugin);
            $pli->show();
        }
    }

    public function newListItem($plugin): PluginListItem
    {
        return new PluginListItem($plugin, $this->out);
    }

    /** Local cache for plugin version info */
    protected static $versions = false;

    /**
     * Lazy-load the set of active plugin version info.
     * Warning: Plugin not modules, it doesn't include core modules.
     * @return array
     */
    public static function getPluginVersions(): array
    {
        if (!is_array(self::$versions)) {
            $plugin_versions = [];
            Event::handle('PluginVersion', [&$plugin_versions]);
            self::$versions = $plugin_versions;
        }
        return self::$versions;
    }

    /**
     * We need a proper name for comparison, that is, without spaces nor the `(section)`
     * Therefore, a plugin named "Diogo Cordeiro (diogo@fc.up.pt)" becomes "DiogoCordeiro"
     *
     * WARNING: You may have to use strtolower() in your situation
     *
     * @param string $plugin_name
     * @return string Name without spaces nor parentheses section
     */
    public static function internalizePluginName(string $plugin_name): string
    {
        $name_without_spaces = str_replace(' ', '', $plugin_name);
        $name_without_spaces_nor_parentheses_section = substr($name_without_spaces, 0, strpos($name_without_spaces . '(', '('));
        return $name_without_spaces_nor_parentheses_section;
    }

    /**
     * It calls self::getPluginVersions() and for each it builds an array with the self::internalizePluginName()
     *
     * @return array
     */
    public static function getActivePluginVersions(): array
    {
        $versions = self::getPluginVersions();
        $active_plugins = [];
        foreach ($versions as $info) {
            $internal_plugin_name = self::internalizePluginName($info['name']);

            // This is sensitive case
            $key = 'disable-' . $internal_plugin_name;
            if (common_config('plugins', $key)) {
                continue;
            }

            $active_plugins[] = $info;
        }
        return $active_plugins;
    }

    /**
     * Checks if a given plugin was loaded (added in config.php with addPlugin())
     *
     * @param string $plugin
     * @return bool
     * @see PluginListItem->metaInfo() Warning: horribly inefficient and may explode!
     */
    public static function isPluginLoaded(string $plugin): bool
    {
        $versions = self::getPluginVersions();
        foreach ($versions as $info) {
            $internal_plugin_name = self::internalizePluginName($info['name']);

            // For a proper comparison, we do it in lower case
            if (strtolower($internal_plugin_name) == strtolower($plugin)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if a given plugin is active (both loaded and not set as inactive in the database)
     *
     * @param string $plugin
     * @return bool
     * @see self::isPluginLoaded() Warning: horribly inefficient and may explode!
     */
    public static function isPluginActive(string $plugin): bool
    {
        $key = 'disable-' . $plugin;
        return self::isPluginLoaded($plugin) && !common_config('plugins', $key);
    }
}

/**
 * Class PluginListItem
 */
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

        $this->out->elementStart('tr', ['id' => 'plugin-' . $this->plugin]);

        // Name and controls
        $this->out->elementStart('td');
        $this->out->elementStart('div');
        if (!empty($meta['homepage'])) {
            $this->out->elementStart('a', ['href' => $meta['homepage']]);
        }
        $this->out->text($this->plugin);
        if (!empty($meta['homepage'])) {
            $this->out->elementEnd('a');
        }
        $this->out->elementEnd('div');

        $form = $this->getControlForm();
        $form->show();

        $delete_form = new PluginDeleteForm($this->out, $this->plugin);
        $delete_form->show();

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
            $this->out->text($meta['description']);
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
        if (PluginList::isPluginActive($this->plugin)) {
            return new PluginDisableForm($this->out, $this->plugin);
        } else {
            return new PluginEnableForm($this->out, $this->plugin);
        }
    }

    /**
     * Grab metadata about this plugin...
     * Warning: horribly inefficient and may explode!
     * Doesn't work for disabled plugins either.
     *
     * @fixme pull structured data from plugin source
     * ^ Maybe by introducing a ini file in each plugin's directory? But a typical instance doesn't have all that many
     * plugins anyway, no need for urgent action
     */
    public function metaInfo()
    {
        $versions = PluginList::getPluginVersions();
        $found = false;

        foreach ($versions as $info) {
            $internal_plugin_name = PluginList::internalizePluginName($info['name']);

            // For a proper comparison, we do it in lower case
            if (strtolower($internal_plugin_name) == strtolower($this->plugin)) {
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
                'rawdescription' => _m('plugin-description', '(The plugin description is unavailable when a plugin hasn\'t been loaded.)')];
        }
    }
}
