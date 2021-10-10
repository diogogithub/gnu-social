<?php

declare(strict_types = 1);

use App\Core\ModuleManager;
use Symfony\Component\Dotenv\Dotenv;

$loader = require \dirname(__DIR__) . '/vendor/autoload.php';

// Load cached env vars if the .env.local.php file exists
// Run "composer dump-env prod" to create it (requires symfony/flex >=1.2)
if (\is_array($env = @include \dirname(__DIR__) . '/.env.local.php') && (!isset($env['APP_ENV']) || ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? $env['APP_ENV']) === $env['APP_ENV'])) {
    foreach ($env as $k => $v) {
        $_ENV[$k] = $_ENV[$k] ?? (isset($_SERVER[$k]) && !str_starts_with($k, 'HTTP_') ? $_SERVER[$k] : $v);
    }
} elseif (!class_exists(Dotenv::class)) {
    throw new RuntimeException('Please run "composer require symfony/dotenv" to load the ".env" files configuring the application.');
} else {
    // load all the .env files
    (new Dotenv())->loadEnv(\dirname(__DIR__) . '/.env');
}

$_SERVER += $_ENV;
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'dev';
$_SERVER['APP_DEBUG'] ??= $_ENV['APP_DEBUG'] ?? 'prod' !== $_SERVER['APP_ENV'];
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = (int) $_SERVER['APP_DEBUG'] || filter_var($_SERVER['APP_DEBUG'], \FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

\define('INSTALLDIR', \dirname(__DIR__));
\define('SRCDIR', INSTALLDIR . '/src');
\define('PUBLICDIR', INSTALLDIR . '/public');
\define('GNUSOCIAL_ENGINE_NAME', 'GNU social');
// MERGE Change to https://gnu.io/social/
\define('GNUSOCIAL_PROJECT_URL', 'https://gnusocial.rocks/');
\define('GNUSOCIAL_ENGINE_URL', GNUSOCIAL_PROJECT_URL);
// MERGE Change to https://git.gnu.io/gnu/gnu-social
\define('GNUSOCIAL_REPOSITORY_URL', 'https://code.undefinedhackers.net/GNUsocial/gnu-social');
// Current base version, major.minor.patch
\define('GNUSOCIAL_BASE_VERSION', '3.0.0');
// 'dev', 'alpha[0-9]+', 'beta[0-9]+', 'rc[0-9]+', 'release'
\define('GNUSOCIAL_LIFECYCLE', 'dev');
\define('GNUSOCIAL_VERSION', GNUSOCIAL_BASE_VERSION . '-' . GNUSOCIAL_LIFECYCLE);
\define('GNUSOCIAL_CODENAME', 'Big bang');

\define('MODULE_CACHE_FILE', INSTALLDIR . '/var/cache/module_manager.php');

/**
 * StatusNet had this string as valid path characters: '\pN\pL\,\!\(\)\.\:\-\_\+\/\=\&\;\%\~\*\$\'\@'
 * Some of those characters can be troublesome when auto-linking plain text. Such as "http://some.com/)"
 * URL encoding should be used whenever a weird character is used, the following strings are not definitive.
 */
\define('URL_REGEX_VALID_PATH_CHARS', '\pN\pL\,\!\.\:\-\_\+\/\@\=\;\%\~\*\(\)');
\define('URL_REGEX_VALID_QSTRING_CHARS', URL_REGEX_VALID_PATH_CHARS . '\&');
\define('URL_REGEX_VALID_FRAGMENT_CHARS', URL_REGEX_VALID_QSTRING_CHARS . '\?\#');
\define('URL_REGEX_EXCLUDED_END_CHARS', '\?\.\,\!\#\:\'');  // don't include these if they are directly after a URL
\define('URL_REGEX_DOMAIN_NAME', '(?:(?!-)[A-Za-z0-9\-]{1,63}(?<!-)\.)+[A-Za-z]{2,10}');

// Work internally in UTC
date_default_timezone_set('UTC');

// Work internally with UTF-8
mb_internal_encoding('UTF-8');

ModuleManager::setLoader($loader);
