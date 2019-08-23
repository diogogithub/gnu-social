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

defined('GNUSOCIAL') || die();

global $config, $_server, $_path;

/**
 * Global configuration setup and management.
 */
class GNUsocial
{
    protected static $config_files = [];
    protected static $have_config;
    protected static $is_api;
    protected static $is_ajax;
    protected static $modules = [];

    /**
     * Configure and instantiate a core module into the current configuration.
     * Class definitions will be loaded from standard paths if necessary.
     * Note that initialization events won't be fired until later.
     *
     * @param string $name class name & module file/subdir name
     * @param array $attrs key/value pairs of public attributes to set on module instance
     *
     * @return bool
     * @throws ServerException if module can't be found
     */
    public static function addModule(string $name, array $attrs = [])
    {
        $name = ucfirst($name);

        if (isset(self::$modules[$name])) {
            // We have already loaded this module. Don't try to
            // do it again with (possibly) different values.
            // Försten till kvarn får mala.
            return true;
        }

        $moduleclass = "{$name}Module";

        if (!class_exists($moduleclass)) {

            $files = [
                "modules/{$moduleclass}.php",
                "modules/{$name}/{$moduleclass}.php"
            ];

            foreach ($files as $file) {
                $fullpath = INSTALLDIR . '/' . $file;
                if (@file_exists($fullpath)) {
                    include_once $fullpath;
                    break;
                }
            }
            if (!class_exists($moduleclass)) {
                throw new ServerException("Module $name not found.", 500);
            }
        }

        // Doesn't this $inst risk being garbage collected or something?
        // TODO: put into a static array that makes sure $inst isn't lost.
        $inst = new $moduleclass();
        foreach ($attrs as $aname => $avalue) {
            $inst->$aname = $avalue;
        }

        // Record activated modules for later display/config dump
        self::$modules[$name] = $attrs;

        return true;
    }

    /**
     * Configure and instantiate a plugin into the current configuration.
     * Class definitions will be loaded from standard paths if necessary.
     * Note that initialization events won't be fired until later.
     *
     * @param string $name class name & module file/subdir name
     * @param array $attrs key/value pairs of public attributes to set on module instance
     *
     * @return bool
     * @throws ServerException if module can't be found
     */
    public static function addPlugin(string $name, array $attrs = [])
    {
        $name = ucfirst($name);

        if (isset(self::$modules[$name])) {
            // We have already loaded this module. Don't try to
            // do it again with (possibly) different values.
            // Försten till kvarn får mala.
            return true;
        }

        $moduleclass = "{$name}Plugin";

        if (!class_exists($moduleclass)) {

            $files = [
                "local/plugins/{$moduleclass}.php",
                "local/plugins/{$name}/{$moduleclass}.php",
                "plugins/{$moduleclass}.php",
                "plugins/{$name}/{$moduleclass}.php"
            ];

            foreach ($files as $file) {
                $fullpath = INSTALLDIR . '/' . $file;
                if (@file_exists($fullpath)) {
                    include_once $fullpath;
                    break;
                }
            }
            if (!class_exists($moduleclass)) {
                throw new ServerException("Plugin $name not found.", 500);
            }
        }

        // Doesn't this $inst risk being garbage collected or something?
        // TODO: put into a static array that makes sure $inst isn't lost.
        $inst = new $moduleclass();
        foreach ($attrs as $aname => $avalue) {
            $inst->$aname = $avalue;
        }

        // Record activated modules for later display/config dump
        self::$modules[$name] = $attrs;

        return true;
    }

    public static function delPlugin($name)
    {
        // Remove our module if it was previously loaded
        $name = ucfirst($name);
        if (isset(self::$modules[$name])) {
            unset(self::$modules[$name]);
        }

        // make sure initPlugins will avoid this
        common_config_set('plugins', 'disable-' . $name, true);

        return true;
    }

    /**
     * Get a list of activated modules in this process.
     * @return array of (string $name, array $args) pairs
     */
    public static function getActiveModules()
    {
        return self::$modules;
    }

    /**
     * Initialize, or re-initialize, GNU social global configuration
     * and modules.
     *
     * If switching site configurations during script execution, be
     * careful when working with leftover objects -- global settings
     * affect many things and they may not behave as you expected.
     *
     * @param $server optional web server hostname for picking config
     * @param $path optional URL path for picking config
     * @param $conffile optional configuration file path
     *
     * @throws ConfigException
     * @throws NoConfigException if config file can't be found
     * @throws ServerException
     */
    public static function init($server = null, $path = null, $conffile = null)
    {
        Router::clear();

        self::initDefaults($server, $path);
        self::loadConfigFile($conffile);

        $sprofile = common_config('site', 'profile');
        if (!empty($sprofile)) {
            self::loadSiteProfile($sprofile);
        }
        // Load settings from database; note we need autoload for this
        Config::loadSettings();

        self::fillConfigVoids();
        self::verifyLoadedConfig();

        self::initModules();
    }

    /**
     * Get identifier of the currently active site configuration
     * @return string
     */
    public static function currentSite()
    {
        return common_config('site', 'nickname');
    }

    /**
     * Change site configuration to site specified by nickname,
     * if set up via Status_network. If not, sites other than
     * the current will fail horribly.
     *
     * May throw exception or trigger a fatal error if the given
     * site is missing or configured incorrectly.
     *
     * @param string $nickname
     * @return bool
     * @throws ConfigException
     * @throws NoConfigException
     * @throws ServerException
     */
    public static function switchSite($nickname)
    {
        if ($nickname == self::currentSite()) {
            return true;
        }

        $sn = Status_network::getKV('nickname', $nickname);
        if (empty($sn)) {
            return false;
            //throw new Exception("No such site nickname '$nickname'");
        }

        $server = $sn->getServerName();
        self::init($server);
    }

    /**
     * Pull all local sites from status_network table.
     *
     * Behavior undefined if site is not configured via Status_network.
     *
     * @return array of nicknames
     */
    public static function findAllSites()
    {
        $sites = [];
        $sn = new Status_network();
        $sn->find();
        while ($sn->fetch()) {
            $sites[] = $sn->nickname;
        }
        return $sites;
    }

    /**
     * Fire initialization events for all instantiated modules.
     */
    protected static function initModules()
    {
        // User config may have already added some of these modules, with
        // maybe configured parameters. The self::addModule function will
        // ignore the new call if it has already been instantiated.

        // Load core modules
        foreach (common_config('plugins', 'core') as $name => $params) {
            call_user_func('self::addModule', $name, $params);
        }

        // Load default plugins
        foreach (common_config('plugins', 'default') as $name => $params) {
            $key = 'disable-' . $name;
            if (common_config('plugins', $key)) {
                continue;
            }

            if (count($params) == 0) {
                self::addPlugin($name);
            } else {
                $keys = array_keys($params);
                if (is_string($keys[0])) {
                    self::addPlugin($name, $params);
                } else {
                    foreach ($params as $paramset) {
                        self::addPlugin($name, $paramset);
                    }
                }
            }
        }

        // XXX: if modules should check the schema at runtime, do that here.
        if (common_config('db', 'schemacheck') == 'runtime') {
            Event::handle('CheckSchema');
        }

        // Give modules and plugins a chance to initialize in a fully-prepared environment
        Event::handle('InitializeModule');
        Event::handle('InitializePlugin');
    }

    /**
     * Quick-check if configuration has been established.
     * Useful for functions which may get used partway through
     * initialization to back off from fancier things.
     *
     * @return bool
     */
    public static function haveConfig()
    {
        return self::$have_config;
    }

    /**
     * Returns a list of configuration files that have
     * been loaded for this instance of GNU social.
     */
    public static function configFiles()
    {
        return self::$config_files;
    }

    public static function isApi()
    {
        return self::$is_api;
    }

    public static function setApi($mode)
    {
        self::$is_api = $mode;
    }

    public static function isAjax()
    {
        return self::$is_ajax;
    }

    public static function setAjax($mode)
    {
        self::$is_ajax = $mode;
    }

    /**
     * Build default configuration array
     * @return array
     */
    protected static function defaultConfig()
    {
        global $_server, $_path;
        require(INSTALLDIR . '/lib/util/default.php');
        return $default;
    }

    /**
     * Establish default configuration based on given or default server and path
     * Sets global $_server, $_path, and $config
     */
    public static function initDefaults($server, $path)
    {
        global $_server, $_path, $config, $_PEAR;

        Event::clearHandlers();
        self::$modules = [];

        // try to figure out where we are. $server and $path
        // can be set by including module, else we guess based
        // on HTTP info.

        if (isset($server)) {
            $_server = $server;
        } else {
            $_server = array_key_exists('SERVER_NAME', $_SERVER) ?
                strtolower($_SERVER['SERVER_NAME']) :
                null;
        }

        if (isset($path)) {
            $_path = $path;
        } else {
            $_path = (array_key_exists('SERVER_NAME', $_SERVER) && array_key_exists('SCRIPT_NAME', $_SERVER)) ?
                self::_sn_to_path($_SERVER['SCRIPT_NAME']) :
                null;
        }

        // Set config values initially to default values
        $default = self::defaultConfig();
        $config = $default;

        // default configuration, overwritten in config.php
        // Keep DB_DataObject's db config synced to ours...

        $config['db'] = &$_PEAR->getStaticProperty('DB_DataObject', 'options');

        $config['db'] = $default['db'];
    }

    public static function loadSiteProfile($name)
    {
        global $config;
        $settings = SiteProfile::getSettings($name);
        $config = array_replace_recursive($config, $settings);
    }

    protected static function _sn_to_path($sn)
    {
        $past_root = substr($sn, 1);
        $last_slash = strrpos($past_root, '/');
        if ($last_slash > 0) {
            $p = substr($past_root, 0, $last_slash);
        } else {
            $p = '';
        }
        return $p;
    }

    /**
     * Load the default or specified configuration file.
     * Modifies global $config and may establish modules.
     *
     * @throws NoConfigException
     * @throws ServerException
     */
    protected static function loadConfigFile($conffile = null)
    {
        global $_server, $_path, $config;

        // From most general to most specific:
        // server-wide, then vhost-wide, then for a path,
        // finally for a dir (usually only need one of the last two).

        if (isset($conffile)) {
            $config_files = [$conffile];
        } else {
            $config_files = ['/etc/gnusocial/config.php',
                '/etc/gnusocial/config.d/' . $_server . '.php'];

            if (strlen($_path) > 0) {
                $config_files[] = '/etc/gnusocial/config.d/' . $_server . '_' . $_path . '.php';
            }

            $config_files[] = INSTALLDIR . '/config.php';
        }

        self::$have_config = false;

        foreach ($config_files as $_config_file) {
            if (@file_exists($_config_file)) {
                // Ignore 0-byte config files
                if (filesize($_config_file) > 0) {
                    include($_config_file);
                    self::$config_files[] = $_config_file;
                    self::$have_config = true;
                }
            }
        }

        if (!self::$have_config) {
            throw new NoConfigException("No configuration file found.",
                $config_files);
        }

        // Check for database server; must exist!

        if (empty($config['db']['database'])) {
            throw new ServerException("No database server for this site.");
        }
    }

    static function fillConfigVoids()
    {
        // special cases on empty configuration options
        if (!common_config('thumbnail', 'dir')) {
            common_config_set('thumbnail', 'dir', File::path('thumb'));
        }
    }

    /**
     * Verify that the loaded config is good. Not complete, but will
     * throw exceptions on common configuration problems I hope.
     *
     * Might make changes to the filesystem, to created dirs, but will
     * not make database changes.
     */
    static function verifyLoadedConfig()
    {
        $mkdirs = [];

        if (common_config('htmlpurifier', 'Cache.DefinitionImpl') === 'Serializer'
            && !is_dir(common_config('htmlpurifier', 'Cache.SerializerPath'))) {
            $mkdirs[common_config('htmlpurifier', 'Cache.SerializerPath')] = 'HTMLPurifier Serializer cache';
        }

        // go through our configurable storage directories
        foreach (['attachments', 'thumbnail'] as $dirtype) {
            $dir = common_config($dirtype, 'dir');
            if (!empty($dir) && !is_dir($dir)) {
                $mkdirs[$dir] = $dirtype;
            }
        }

        // try to create those that are not directories
        foreach ($mkdirs as $dir => $description) {
            if (is_file($dir)) {
                throw new ConfigException('Expected directory for ' . _ve($description) . ' is a file!');
            }
            if (!mkdir($dir)) {
                throw new ConfigException('Could not create directory for ' . _ve($description) . ': ' . _ve($dir));
            }
            if (!chmod($dir, 0775)) {
                common_log(LOG_WARNING, 'Could not chmod 0775 on directory for ' . _ve($description) . ': ' . _ve($dir));
            }
        }

        if (!is_array(common_config('public', 'autosource'))) {
            throw new ConfigException('Configuration option public/autosource is not an array.');
        }
    }

    /**
     * Are we running from the web with HTTPS?
     *
     * @return boolean true if we're running with HTTPS; else false
     */

    static function isHTTPS()
    {
        if (common_config('site', 'sslproxy')) {
            return true;
        }

        // There are some exceptions to this; add them here!
        if (empty($_SERVER['HTTPS'])) {
            return false;
        }

        // If it is _not_ "off", it is on, so "true".
        return strtolower($_SERVER['HTTPS']) !== 'off';
    }

    /**
     * Can we use HTTPS? Then do! Only return false if it's not configured ("never").
     */
    static function useHTTPS()
    {
        return self::isHTTPS() || common_config('site', 'ssl') != 'never';
    }
}

class NoConfigException extends Exception
{
    public $configFiles;

    function __construct($msg, $configFiles)
    {
        parent::__construct($msg);
        $this->configFiles = $configFiles;
    }
}
