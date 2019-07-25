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
 * Installation lib
 *
 * @package   Installation
 * @author    Adrian Lang <mail@adrianlang.de>
 * @author    Brenda Wallace <shiny@cpan.org>
 * @author    Brett Taylor <brett@webfroot.co.nz>
 * @author    Brion Vibber <brion@pobox.com>
 * @author    CiaranG <ciaran@ciarang.com>
 * @author    Craig Andrews <candrews@integralblue.com>
 * @author    Eric Helgeson <helfire@Erics-MBP.local>
 * @author    Evan Prodromou <evan@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Robin Millette <millette@controlyourself.ca>
 * @author    Sarven Capadisli <csarven@status.net>
 * @author    Tom Adams <tom@holizz.com>
 * @author    Zach Copley <zach@status.net>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

abstract class Installer
{
    /** Web site info */
    public $sitename;
    public $server;
    public $path;
    public $fancy;
    public $siteProfile;
    public $ssl;
    /** DB info */
    public $host;
    public $database;
    public $dbtype;
    public $username;
    public $password;
    public $db;
    /** Storage info */
    public $avatarDir;
    public $fileDir;
    /** Administrator info */
    public $adminNick;
    public $adminPass;
    public $adminEmail;
    /** Should we skip writing the configuration file? */
    public $skipConfig = false;

    public static $dbModules = [
        'mysql' => [
            'name' => 'MariaDB 10.3+',
            'check_module' => 'mysqli',
            'scheme' => 'mysqli', // DSN prefix for PEAR::DB
        ],
        /*'pgsql' => [
            'name' => 'PostgreSQL',
            'check_module' => 'pgsql',
            'scheme' => 'pgsql', // DSN prefix for PEAR::DB
        ]*/
    ];

    /**
     * Attempt to include a PHP file and report if it worked, while
     * suppressing the annoying warning messages on failure.
     * @param string $filename
     * @return bool
     */
    private function haveIncludeFile(string $filename): bool
    {
        $old = error_reporting(error_reporting() & ~E_WARNING);
        $ok = include_once($filename);
        error_reporting($old);
        return $ok;
    }

    /**
     * Check if all is ready for installation
     *
     * @return bool
     */
    public function checkPrereqs(): bool
    {
        $pass = true;

        $config = INSTALLDIR . '/config.php';
        if (!$this->skipConfig && file_exists($config)) {
            if (!is_writable($config) || filesize($config) > 0) {
                if (filesize($config) == 0) {
                    $this->warning('Config file "config.php" already exists and is empty, but is not writable.');
                } else {
                    $this->warning('Config file "config.php" already exists.');
                }
                $pass = false;
            }
        }

        if (version_compare(PHP_VERSION, '7.3.0', '<')) {
            $this->warning('Require PHP version 7.3.0 or greater.');
            $pass = false;
        }

        $reqs = ['bcmath', 'curl', 'dom', 'gd', 'intl', 'json', 'mbstring', 'openssl', 'simplexml', 'xml', 'xmlwriter'];
        foreach ($reqs as $req) {
            // Checks if a php extension is both installed and loaded
            if (!extension_loaded($req)) {
                $this->warning(sprintf('Cannot load required extension: <code>%s</code>', $req));
                $pass = false;
            }
        }

        // Make sure we have at least one database module available
        $missingExtensions = [];
        foreach (self::$dbModules as $type => $info) {
            if (!extension_loaded($info['check_module'])) {
                $missingExtensions[] = $info['check_module'];
            }
        }

        if (count($missingExtensions) == count(self::$dbModules)) {
            $req = implode(', ', $missingExtensions);
            $this->warning(sprintf('Cannot find a database extension. You need at least one of %s.', $req));
            $pass = false;
        }

        // @fixme this check seems to be insufficient with Windows ACLs
        if (!$this->skipConfig && !is_writable(INSTALLDIR)) {
            $this->warning(
                sprintf('Cannot write config file to: <code>%s</code></p>', INSTALLDIR),
                sprintf('On your server, try this command: <code>chmod a+w %s</code>', INSTALLDIR)
            );
            $pass = false;
        }

        // Check the subdirs used for file uploads
        // TODO get another flag for this --skipFileSubdirCreation
        if (!$this->skipConfig) {
            define('GNUSOCIAL', true);
            define('STATUSNET', true);
            require_once INSTALLDIR . '/lib/language.php';
            $_server = $this->server;
            $_path = $this->path; // We won't be using those so it's safe to do this small hack
            require_once INSTALLDIR . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'util.php';
            require_once INSTALLDIR . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'default.php';
            $fileSubdirs = [
                empty($this->avatarDir) ? $default['avatar']['dir'] : $this->avatarDir,
                empty($this->fileDir) ? $default['attachments']['dir'] : $this->fileDir
            ];
            unset($default);
            foreach ($fileSubdirs as $fileFullPath) {
                if (!file_exists($fileFullPath)) {
                    $this->warning(
                        sprintf('GNU social was unable to create a directory on this path: %s', $fileFullPath),
                        'Either create that directory with the right permissions so that GNU social can use it or '.
                        'set the necessary permissions and it will be created.'
                    );
                    $pass = $pass && mkdir($fileFullPath);
                } elseif (!is_dir($fileFullPath)) {
                    $this->warning(
                        sprintf('GNU social expected a directory but found something else on this path: %s', $fileFullPath),
                        'Either make sure it goes to a directory or remove it and a directory will be created.'
                    );
                    $pass = false;
                } elseif (!is_writable($fileFullPath)) {
                    $this->warning(
                        sprintf('Cannot write to directory: <code>%s</code>', $fileFullPath),
                        sprintf('On your server, try this command: <code>chmod a+w %s</code>', $fileFullPath)
                    );
                    $pass = false;
                }
            }
        }
        return $pass;
    }

    /**
     * Basic validation on the database parameters
     * Side effects: error output if not valid
     *
     * @return bool success
     */
    public function validateDb(): bool
    {
        $fail = false;

        if (empty($this->host)) {
            $this->updateStatus("No hostname specified.", true);
            $fail = true;
        }

        if (empty($this->database)) {
            $this->updateStatus("No database specified.", true);
            $fail = true;
        }

        if (empty($this->username)) {
            $this->updateStatus("No username specified.", true);
            $fail = true;
        }

        if (empty($this->sitename)) {
            $this->updateStatus("No sitename specified.", true);
            $fail = true;
        }

        return !$fail;
    }

    /**
     * Basic validation on the administrator user parameters
     * Side effects: error output if not valid
     *
     * @return bool success
     */
    public function validateAdmin(): bool
    {
        $fail = false;

        if (empty($this->adminNick)) {
            $this->updateStatus("No initial user nickname specified.", true);
            $fail = true;
        }
        if ($this->adminNick && !preg_match('/^[0-9a-z]{1,64}$/', $this->adminNick)) {
            $this->updateStatus('The user nickname "' . htmlspecialchars($this->adminNick) .
                '" is invalid; should be plain letters and numbers no longer than 64 characters.', true);
            $fail = true;
        }

        // @fixme hardcoded list; should use Nickname::isValid()
        // if/when it's safe to have loaded the infrastructure here
        $blacklist = ['main', 'panel', 'twitter', 'settings', 'rsd.xml', 'favorited', 'featured', 'favoritedrss', 'featuredrss', 'rss', 'getfile', 'api', 'groups', 'group', 'peopletag', 'tag', 'user', 'message', 'conversation', 'notice', 'attachment', 'search', 'index.php', 'doc', 'opensearch', 'robots.txt', 'xd_receiver.html', 'facebook', 'activity'];
        if (in_array($this->adminNick, $blacklist)) {
            $this->updateStatus('The user nickname "' . htmlspecialchars($this->adminNick) .
                '" is reserved.', true);
            $fail = true;
        }

        if (empty($this->adminPass)) {
            $this->updateStatus("No initial user password specified.", true);
            $fail = true;
        }

        return !$fail;
    }

    /**
     * Make sure a site profile was selected
     *
     * @return bool success
     */
    public function validateSiteProfile(): bool
    {
        if (empty($this->siteProfile)) {
            $this->updateStatus("No site profile selected.", true);
            return false;
        }

        return true;
    }

    /**
     * Set up the database with the appropriate function for the selected type...
     * Saves database info into $this->db.
     *
     * @fixme escape things in the connection string in case we have a funny pass etc
     * @return mixed array of database connection params on success, false on failure
     * @throws Exception
     */
    public function setupDatabase()
    {
        if ($this->db) {
            throw new Exception("Bad order of operations: DB already set up.");
        }
        $this->updateStatus("Starting installation...");

        if (empty($this->password)) {
            $auth = '';
        } else {
            $auth = ":$this->password";
        }
        $scheme = self::$dbModules[$this->dbtype]['scheme'];
        $dsn = "{$scheme}://{$this->username}{$auth}@{$this->host}/{$this->database}";

        $this->updateStatus("Checking database...");
        $conn = $this->connectDatabase($dsn);

        if (!$conn instanceof DB_common) {
            // Is not the right instance
            throw new Exception('Cannot connect to database: ' . $conn->getMessage());
        }

        // ensure database encoding is UTF8
        $conn->query('SET NAMES utf8mb4');
        if ($this->dbtype == 'mysql') {
            $server_encoding = $conn->getRow("SHOW VARIABLES LIKE 'character_set_server'")[1];
            if ($server_encoding != 'utf8mb4') {
                $this->updateStatus("GNU social requires UTF8 character encoding. Your database is " . htmlentities($server_encoding));
                return false;
            }
        } elseif ($this->dbtype == 'pgsql') {
            $server_encoding = $conn->getRow('SHOW server_encoding')[0];
            if ($server_encoding != 'UTF8') {
                $this->updateStatus("GNU social requires UTF8 character encoding. Your database is " . htmlentities($server_encoding));
                return false;
            }
        }

        $res = $this->updateStatus("Creating database tables...");
        if (!$this->createCoreTables($conn)) {
            $this->updateStatus("Error creating tables.", true);
            return false;
        }

        foreach (['sms_carrier' => 'SMS carrier',
                     'notice_source' => 'notice source',
                     'foreign_services' => 'foreign service']
                 as $scr => $name) {
            $this->updateStatus(sprintf("Adding %s data to database...", $name));
            $res = $this->runDbScript($scr . '.sql', $conn);
            if ($res === false) {
                $this->updateStatus(sprintf("Can't run %s script.", $name), true);
                return false;
            }
        }

        $db = ['type' => $this->dbtype, 'database' => $dsn];
        return $db;
    }

    /**
     * Open a connection to the database.
     *
     * @param string $dsn
     * @return DB|DB_Error
     */
    public function connectDatabase(string $dsn)
    {
        global $_DB;
        return $_DB->connect($dsn);
    }

    /**
     * Create core tables on the given database connection.
     *
     * @param DB_common $conn
     * @return bool
     */
    public function createCoreTables(DB_common $conn): bool
    {
        $schema = Schema::get($conn);
        $tableDefs = $this->getCoreSchema();
        foreach ($tableDefs as $name => $def) {
            if (defined('DEBUG_INSTALLER')) {
                echo " $name ";
            }
            $schema->ensureTable($name, $def);
        }
        return true;
    }

    /**
     * Fetch the core table schema definitions.
     *
     * @return array of table names => table def arrays
     */
    public function getCoreSchema(): array
    {
        $schema = [];
        include INSTALLDIR . '/db/core.php';
        return $schema;
    }

    /**
     * Return a parseable PHP literal for the given value.
     * This will include quotes for strings, etc.
     *
     * @param mixed $val
     * @return string
     */
    public function phpVal($val): string
    {
        return var_export($val, true);
    }

    /**
     * Return an array of parseable PHP literal for the given values.
     * These will include quotes for strings, etc.
     *
     * @param mixed $map
     * @return array
     */
    public function phpVals($map): array
    {
        return array_map([$this, 'phpVal'], $map);
    }

    /**
     * Write a stock configuration file.
     *
     * @return bool success
     *
     * @fixme escape variables in output in case we have funny chars, apostrophes etc
     */
    public function writeConf(): bool
    {
        $vals = $this->phpVals([
            'sitename' => $this->sitename,
            'server' => $this->server,
            'path' => $this->path,
            'ssl' => in_array($this->ssl, ['never', 'always'])
                ? $this->ssl
                : 'never',
            'db_database' => $this->db['database'],
            'db_type' => $this->db['type']
        ]);

        // assemble configuration file in a string
        $cfg = "<?php\n" .
            "if (!defined('GNUSOCIAL')) { exit(1); }\n\n" .

            // site name
            "\$config['site']['name'] = {$vals['sitename']};\n\n" .

            // site location
            "\$config['site']['server'] = {$vals['server']};\n" .
            "\$config['site']['path'] = {$vals['path']}; \n\n" .
            "\$config['site']['ssl'] = {$vals['ssl']}; \n\n" .

            // checks if fancy URLs are enabled
            ($this->fancy ? "\$config['site']['fancy'] = true;\n\n" : '') .

            // database
            "\$config['db']['database'] = {$vals['db_database']};\n\n" .
            ($this->db['type'] == 'pgsql' ? "\$config['db']['quote_identifiers'] = true;\n\n" : '') .
            "\$config['db']['type'] = {$vals['db_type']};\n\n" .

            "// Uncomment below for better performance. Just remember you must run\n" .
            "// php scripts/checkschema.php whenever your enabled plugins change!\n" .
            "//\$config['db']['schemacheck'] = 'script';\n\n";

        // Normalize line endings for Windows servers
        $cfg = str_replace("\n", PHP_EOL, $cfg);

        // write configuration file out to install directory
        $res = file_put_contents(INSTALLDIR . '/config.php', $cfg);

        return $res;
    }

    /**
     * Write the site profile. We do this after creating the initial user
     * in case the site profile is set to single user. This gets around the
     * 'chicken-and-egg' problem of the system requiring a valid user for
     * single user mode, before the intial user is actually created. Yeah,
     * we should probably do this in smarter way.
     *
     * @return int res number of bytes written
     */
    public function writeSiteProfile(): int
    {
        $vals = $this->phpVals([
            'site_profile' => $this->siteProfile,
            'nickname' => $this->adminNick
        ]);

        $cfg =
            // site profile
            "\$config['site']['profile'] = {$vals['site_profile']};\n";

        if ($this->siteProfile == "singleuser") {
            $cfg .= "\$config['singleuser']['nickname'] = {$vals['nickname']};\n\n";
        } else {
            $cfg .= "\n";
        }

        // Normalize line endings for Windows servers
        $cfg = str_replace("\n", PHP_EOL, $cfg);

        // write configuration file out to install directory
        $res = file_put_contents(INSTALLDIR . '/config.php', $cfg, FILE_APPEND);

        return $res;
    }

    /**
     * Install schema into the database
     *
     * @param string $filename location of database schema file
     * @param DB_common $conn connection to database
     *
     * @return bool - indicating success or failure
     */
    public function runDbScript(string $filename, DB_common $conn): bool
    {
        $sql = trim(file_get_contents(INSTALLDIR . '/db/' . $filename));
        $stmts = explode(';', $sql);
        foreach ($stmts as $stmt) {
            $stmt = trim($stmt);
            if (!mb_strlen($stmt)) {
                continue;
            }
            try {
                $res = $conn->query($stmt);
            } catch (Exception $e) {
                $error = $e->getMessage();
                $this->updateStatus("ERROR ($error) for SQL '$stmt'");
                return false;
            }
        }
        return true;
    }

    /**
     * Create the initial admin user account.
     * Side effect: may load portions of GNU social framework.
     * Side effect: outputs program info
     */
    public function registerInitialUser(): bool
    {
        // initalize hostname from install arguments, so it can be used to find
        // the /etc config file from the commandline installer
        $server = $this->server;
        require_once INSTALLDIR . '/lib/common.php';

        $data = ['nickname' => $this->adminNick,
            'password' => $this->adminPass,
            'fullname' => $this->adminNick];
        if ($this->adminEmail) {
            $data['email'] = $this->adminEmail;
        }
        try {
            $user = User::register($data, true);    // true to skip email sending verification
        } catch (Exception $e) {
            return false;
        }

        // give initial user carte blanche

        $user->grantRole('owner');
        $user->grantRole('moderator');
        $user->grantRole('administrator');

        return true;
    }

    /**
     * The beef of the installer!
     * Create database, config file, and admin user.
     *
     * Prerequisites: validation of input data.
     *
     * @return bool success
     */
    public function doInstall(): bool
    {
        global $config;

        $this->updateStatus("Initializing...");
        ini_set('display_errors', 1);
        error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
        if (!defined('GNUSOCIAL')) {
            define('GNUSOCIAL', true);
        }
        if (!defined('STATUSNET')) {
            define('STATUSNET', true);
        }

        require_once INSTALLDIR . '/lib/framework.php';
        GNUsocial::initDefaults($this->server, $this->path);

        if ($this->siteProfile == "singleuser") {
            // Until we use ['site']['profile']==='singleuser' everywhere
            $config['singleuser']['enabled'] = true;
        }

        try {
            $this->db = $this->setupDatabase();
            if (!$this->db) {
                // database connection failed, do not move on to create config file.
                return false;
            }
        } catch (Exception $e) {
            // Lower-level DB error!
            $this->updateStatus("Database error: " . $e->getMessage(), true);
            return false;
        }

        if (!$this->skipConfig) {
            // Make sure we can write to the file twice
            $oldUmask = umask(000);

            $this->updateStatus("Writing config file...");
            $res = $this->writeConf();

            if (!$res) {
                $this->updateStatus("Can't write config file.", true);
                return false;
            }
        }

        if (!empty($this->adminNick)) {
            // Okay, cross fingers and try to register an initial user
            if ($this->registerInitialUser()) {
                $this->updateStatus(
                    "An initial user with the administrator role has been created."
                );
            } else {
                $this->updateStatus(
                    "Could not create initial user account.",
                    true
                );
                return false;
            }
        }

        if (!$this->skipConfig) {
            $this->updateStatus("Setting site profile...");
            $res = $this->writeSiteProfile();

            if (!$res) {
                $this->updateStatus("Can't write to config file.", true);
                return false;
            }

            // Restore original umask
            umask($oldUmask);
            // Set permissions back to something decent
            chmod(INSTALLDIR . '/config.php', 0644);
        }

        $scheme = $this->ssl === 'always' ? 'https' : 'http';
        $link = "{$scheme}://{$this->server}/{$this->path}";

        $this->updateStatus("GNU social has been installed at $link");
        $this->updateStatus(
            '<strong>DONE!</strong> You can visit your <a href="' . htmlspecialchars($link) . '">new GNU social site</a> (log in as "' . htmlspecialchars($this->adminNick) . '"). If this is your first GNU social install, make your experience the best possible by visiting our resource site to join the <a href="https://gnu.io/social/resources/">mailing list or IRC</a>. <a href="' . htmlspecialchars($link) . '/doc/faq">FAQ is found here</a>.'
        );

        return true;
    }

    /**
     * Output a pre-install-time warning message
     * @param string $message HTML ok, but should be plaintext-able
     * @param string $submessage HTML ok, but should be plaintext-able
     */
    abstract public function warning(string $message, string $submessage = '');

    /**
     * Output an install-time progress message
     * @param string $status HTML ok, but should be plaintext-able
     * @param bool $error true if this should be marked as an error condition
     */
    abstract public function updateStatus(string $status, bool $error = false);
}
