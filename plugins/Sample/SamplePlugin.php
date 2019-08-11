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

/**
 * A sample plugin to show best practices for GNU social plugins
 *
 * @package   GNU social
 * @author    Brion Vibber <brionv@status.net>
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

// This check helps protect against security problems;
// your code file can't be executed directly from the web.
defined('GNUSOCIAL') || die();

/**
 * Sample plugin main class
 *
 * Each plugin requires a main class to interact with the StatusNet system.
 *
 * The main class usually extends the Plugin class that comes with StatusNet.
 *
 * The class has standard-named methods that will be called when certain events
 * happen in the code base. These methods have names like 'onX' where X is an
 * event name (see EVENTS.txt for the list of available events). Event handlers
 * have pre-defined arguments, based on which event they're handling. A typical
 * event handler:
 *
 *    function onSomeEvent($paramA, &$paramB)
 *    {
 *        if ($paramA == 'jed') {
 *            throw new Exception(sprintf(_m("Invalid parameter %s"), $paramA));
 *        }
 *        $paramB = 'spock';
 *        return true;
 *    }
 *
 * Event handlers must return a boolean value. If they return false, all other
 * event handlers for this event (in other plugins) will be skipped, and in some
 * cases the default processing for that event would be skipped. This is great for
 * replacing the default action of an event.
 *
 * If the handler returns true, processing of other event handlers and the default
 * processing will continue. This is great for extending existing functionality.
 *
 * If the handler throws an exception, processing will stop, and the exception's
 * error will be shown to the user.
 *
 * To install a plugin (like this one), site admins add the following code to
 * their config.php file:
 *
 *     addPlugin('Sample');
 *
 * Plugins must be installed in one of the following directories:
 *
 *     local/plugins/{$pluginclass}.php
 *     local/plugins/{$name}/{$pluginclass}.php
 *     local/{$pluginclass}.php
 *     local/{$name}/{$pluginclass}.php
 *     plugins/{$pluginclass}.php
 *     plugins/{$name}/{$pluginclass}.php
 *
 * Here, {$name} is the name of the plugin, like 'Sample', and {$pluginclass} is
 * the name of the main class, like 'SamplePlugin'. Plugins that are part of the
 * main StatusNet distribution go in 'plugins' and third-party or local ones go
 * in 'local'.
 *
 * Simple plugins can be implemented as a single plugin. Others are more complex
 * and require additional plugins; these should use their own directory, like
 * 'local/plugins/{$name}/'. All files related to the plugin, including images,
 * JavaScript, CSS, external libraries or PHP plugins should go in the plugin
 * directory.
 *
 * @category  Sample
 * @package   GNU social
 * @author    Brion Vibber <brionv@status.net>
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class SamplePlugin extends Plugin
{
    // Versions start at 0.1.0 in Semver
    const PLUGIN_VERSION = '0.1.0';

    /**
     * Plugins are configured using public instance attributes. To set
     * their values, site administrators use this syntax:
     *
     * addPlugin('Sample', ['attr1' => 'foo', 'attr2' => 'bar']);
     *
     * The same plugin class can be initialized multiple times with different
     * arguments:
     *
     * addPlugin('EmailNotify', ['sendTo' => 'evan@status.net']);
     * addPlugin('EmailNotify', ['sendTo' => 'brionv@status.net']);
     *
     */
    public $attr1 = null;
    public $attr2 = null;

    /**
     * Initializer for this plugin
     *
     * Plugins overload this method to do any initialization they need,
     * like connecting to remote servers or creating paths or so on.
     *
     * @return bool hook value; true means continue processing, false means stop.
     */
    public function initialize(): bool
    {
        return true;
    }

    /**
     * Cleanup for this plugin
     *
     * Plugins overload this method to do any cleanup they need,
     * like disconnecting from remote servers or deleting temp files or so on.
     *
     * @return bool hook value; true means continue processing, false means stop.
     */
    public function cleanup(): bool
    {
        return true;
    }

    /**
     * Database schema setup
     *
     * Plugins can add their own tables to the StatusNet database. Plugins
     * should use StatusNet's schema interface to add or delete tables. The
     * ensureTable() method provides an easy way to ensure a table's structure
     * and availability.
     *
     * By default, the schema is checked every time StatusNet is run (say, when
     * a Web page is hit). Admins can configure their systems to only check the
     * schema when the checkschema.php script is run, greatly improving performance.
     * However, they need to remember to run that script after installing or
     * upgrading a plugin!
     *
     * @return bool hook value; true means continue processing, false means stop.
     * @see Schema
     * @see ColumnDef
     */
    public function onCheckSchema(): bool
    {
        $schema = Schema::get();

        // For storing user-submitted flags on profiles
        $schema->ensureTable('user_greeting_count', User_greeting_count::schemaDef());
        return true;
    }

    /**
     * Map URLs to actions
     *
     * This event handler lets the plugin map URLs on the site to actions (and
     * thus an action handler class). Note that the action handler class for an
     * action will be named 'FoobarAction', where action = 'foobar'. The class
     * must be loaded in the onAutoload() method.
     *
     * @param URLMapper $m path-to-action mapper
     *
     * @return bool hook value; true means continue processing, false means stop.
     * @throws Exception If it can't connect our required routes
     */
    public function onRouterInitialized(URLMapper $m): bool
    {
        $m->connect(
            'main/hello',
            ['action' => 'hello']
        );
        return true;
    }

    /**
     * Modify the default menu to link to our custom action
     *
     * Using event handlers, it's possible to modify the default UI for pages
     * almost without limit. In this method, we add a menu item to the default
     * primary menu for the interface to link to our action.
     *
     * The Action class provides a rich set of events to hook, as well as output
     * methods.
     *
     * @param Action $action The current action handler. Use this to
     *                       do any output.
     *
     * @return bool hook value; true means continue processing, false means stop.
     *
     * @throws Exception
     * @see Action
     */
    public function onEndPrimaryNav(Action $action): bool
    {
        // common_local_url() gets the correct URL for the action name
        // we provide
        $action->menuItem(
            common_local_url('hello'),
            // TRANS: Menu item in sample plugin.
            _m('Hello'),
            // TRANS: Menu item title in sample plugin.
            _m('A warm greeting'),
            false,
            'nav_hello'
        );
        return true;
    }

    /**
     * Plugin version information/meta-data
     *
     * @param array $versions
     * @return bool hook true
     * @throws Exception
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'Sample',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Brion Vibber, Evan Prodromou',
            'homepage' => 'https://git.gnu.io/gnu/gnu-social/tree/master/plugins/Sample',
            'rawdescription' =>
            // TRANS: Plugin description.
                _m('A sample plugin to show basics of development for new hackers.')
        ];
        return true;
    }
}
