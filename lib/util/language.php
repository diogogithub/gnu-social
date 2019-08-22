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
 * Utility functions for i18n
 *
 * @category  I18n
 * @package   GNU social
 * @author    Matthew Gregg <matthew.gregg@gmail.com>
 * @author    Ciaran Gultnieks <ciaran@ciarang.com>
 * @author    Evan Prodromou <evan@status.net>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2010-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

// Locale category constants are usually predefined, but may not be
// on some systems such as Win32.
$LC_CATEGORIES = ['LC_CTYPE',
                  'LC_NUMERIC',
                  'LC_TIME',
                  'LC_COLLATE',
                  'LC_MONETARY',
                  'LC_MESSAGES',
                  'LC_ALL'];
foreach ($LC_CATEGORIES as $key => $name) {
    if (!defined($name)) {
        define($name, $key);
    }
}

if (!function_exists('dpgettext')) {
    /**
     * Context-aware dgettext wrapper; use when messages in different contexts
     * won't be distinguished from the English source but need different translations.
     * The context string will appear as msgctxt in the .po files.
     *
     * Not currently exposed in PHP's gettext module; implemented to be compat
     * with gettext.h's macros.
     *
     * @param string $domain domain identifier
     * @param string $context context identifier, should be some key like "menu|file"
     * @param string $msg English source text
     * @return string original or translated message
     */
    function dpgettext($domain, $context, $msg)
    {
        $msgid = $context . "\004" . $msg;
        $out = dcgettext($domain, $msgid, LC_MESSAGES);
        if ($out == $msgid) {
            return $msg;
        } else {
            return $out;
        }
    }
}

if (!function_exists('pgettext')) {
    /**
     * Context-aware gettext wrapper; use when messages in different contexts
     * won't be distinguished from the English source but need different translations.
     * The context string will appear as msgctxt in the .po files.
     *
     * Not currently exposed in PHP's gettext module; implemented to be compat
     * with gettext.h's macros.
     *
     * @param string $context context identifier, should be some key like "menu|file"
     * @param string $msgid English source text
     * @return string original or translated message
     */
    function pgettext($context, $msgid)
    {
        return dpgettext(textdomain(NULL), $context, $msgid);
    }
}

if (!function_exists('dnpgettext')) {
    /**
     * Context-aware dngettext wrapper; use when messages in different contexts
     * won't be distinguished from the English source but need different translations.
     * The context string will appear as msgctxt in the .po files.
     *
     * Not currently exposed in PHP's gettext module; implemented to be compat
     * with gettext.h's macros.
     *
     * @param string $domain domain identifier
     * @param string $context context identifier, should be some key like "menu|file"
     * @param string $msg singular English source text
     * @param string $plural plural English source text
     * @param int $n number of items to control plural selection
     * @return string original or translated message
     */
    function dnpgettext($domain, $context, $msg, $plural, $n)
    {
        $msgid = $context . "\004" . $msg;
        $out = dcngettext($domain, $msgid, $plural, $n, LC_MESSAGES);
        if ($out == $msgid) {
            return $msg;
        } else {
            return $out;
        }
    }
}

if (!function_exists('npgettext')) {
    /**
     * Context-aware ngettext wrapper; use when messages in different contexts
     * won't be distinguished from the English source but need different translations.
     * The context string will appear as msgctxt in the .po files.
     *
     * Not currently exposed in PHP's gettext module; implemented to be compat
     * with gettext.h's macros.
     *
     * @param string $context context identifier, should be some key like "menu|file"
     * @param string $msgid singular English source text
     * @param string $plural plural English source text
     * @param int $n number of items to control plural selection
     * @return string original or translated message
     */
    function npgettext($context, $msgid, $plural, $n)
    {
        return dnpgettext(textdomain(NULL), $msgid, $plural, $n, LC_MESSAGES);
    }
}

/**
 * Shortcut for *gettext functions with smart domain detection.
 *
 * If calling from a plugin, this function checks which plugin was
 * being called from and uses that as text domain, which will have
 * been set up during plugin initialization.
 *
 * Also handles plurals and contexts depending on what parameters
 * are passed to it:
 *
 *   gettext -> _m($msg)
 *  ngettext -> _m($msg1, $msg2, $n)
 *  pgettext -> _m($ctx, $msg)
 * npgettext -> _m($ctx, $msg1, $msg2, $n)
 *
 * @fixme may not work properly in eval'd code
 *
 * @param string $msg
 * @return string
 * @throws Exception
 */
function _m($msg/*, ...*/)
{
    $domain = _mdomain(debug_backtrace());
    $args = func_get_args();
    switch (count($args)) {
        case 1:
            return dgettext($domain, $msg);
        case 2:
            return dpgettext($domain, $args[0], $args[1]);
        case 3:
            return dngettext($domain, $args[0], $args[1], $args[2]);
        case 4:
            return dnpgettext($domain, $args[0], $args[1], $args[2], $args[3]);
        default:
            throw new Exception("Bad parameter count to _m()");
    }
}

/**
 * Looks for which plugin we've been called from to set the gettext domain;
 * if not in a plugin subdirectory, we'll use the default 'statusnet'.
 *
 * Note: we can't return null for default domain since most of the PHP gettext
 * wrapper functions turn null into "" before passing to the backend library.
 *
 * @param array $backtrace debug_backtrace() output
 * @return string
 * @private
 * @fixme could explode if SN is under a 'plugins' folder or share name.
 */
function _mdomain($backtrace)
{
    /*
      0 =>
        array
          'file' => string '/var/www/mublog/plugins/FeedSub/FeedSubPlugin.php' (length=49)
          'line' => int 77
          'function' => string '_m' (length=2)
          'args' =>
            array
              0 => &string 'Feeds' (length=5)
    */
    static $cached;
    $path = $backtrace[0]['file'];
    if (!isset($cached[$path])) {
        if (DIRECTORY_SEPARATOR !== '/') {
            $path = strtr($path, DIRECTORY_SEPARATOR, '/');
        }
        $plug = strpos($path, '/plugins/');
        if ($plug === false) {
            // We're not in a plugin; return default domain.
            $final = 'statusnet';
        } else {
            $cut = $plug + 9;
            $cut2 = strpos($path, '/', $cut);
            if ($cut2) {
                $final = substr($path, $cut, $cut2 - $cut);
            } else {
                // We might be running directly from the plugins dir?
                // If so, there's no place to store locale info.
                $final = 'statusnet';
            }
        }
        $cached[$path] = $final;
    }
    return $cached[$path];
}


/**
 * Content negotiation for language codes
 *
 * @param $http_accept_lang_header string HTTP Accept-Language header
 * @return string language code for best language match, false otherwise
 */

function client_preferred_language($http_accept_lang_header)
{
    $client_langs = [];

    $all_languages = common_config('site', 'languages');

    preg_match_all('"(((\S\S)-?(\S\S)?)(;q=([0-9.]+))?)\s*(,\s*|$)"',
        strtolower($http_accept_lang_header), $http_langs);

    for ($i = 0; $i < count($http_langs); ++$i) {
        if (!empty($http_langs[2][$i])) {
            // if no q default to 1.0
            $client_langs[$http_langs[2][$i]] =
                ($http_langs[6][$i] ? (float)$http_langs[6][$i] : 1.0 - ($i * 0.01));
        }
        if (!empty($http_langs[3][$i]) && empty($client_langs[$http_langs[3][$i]])) {
            // if a catchall default 0.01 lower
            $client_langs[$http_langs[3][$i]] =
                ($http_langs[6][$i] ? (float)$http_langs[6][$i] - 0.01 : 0.99);
        }
    }
    // sort in descending q
    arsort($client_langs);

    foreach ($client_langs as $lang => $q) {
        if (isset($all_languages[$lang])) {
            return ($all_languages[$lang]['lang']);
        }
    }
    return false;
}

/**
 * returns a simple code -> name mapping for languages
 *
 * @return array map of available languages by code to language name.
 */

function get_nice_language_list()
{
    $nice_lang = [];

    $all_languages = common_config('site', 'languages');

    foreach ($all_languages as $lang) {
        $nice_lang = $nice_lang + array($lang['lang'] => $lang['name']);
    }
    return $nice_lang;
}

/*
 * Check whether a language is right-to-left
 *
 * @param string $lang language code of the language to check
 *
 * @return boolean true if language is rtl
 */

function is_rtl($lang_value)
{
        foreach (common_config('site', 'languages') as $code => $info) {
                if ($lang_value == $info['lang']) {
                        return $info['direction'] == 'rtl';
                }
        }
}

/**
 * Get a list of all languages that are enabled in the default config
 *
 * This should ONLY be called when setting up the default config in common.php.
 * Any other attempt to get a list of languages should instead call
 * common_config('site','languages')
 *
 * @return array mapping of language codes to language info
 */
function get_all_languages()
{
    return [
        'af'        => ['q' => 0.8, 'lang' => 'af', 'name' => 'Afrikaans', 'direction' => 'ltr'],
        'ar'        => ['q' => 0.8, 'lang' => 'ar', 'name' => 'Arabic', 'direction' => 'rtl'],
        'ast'       => ['q' => 1, 'lang' => 'ast', 'name' => 'Asturian', 'direction' => 'ltr'],
        'eu'        => ['q' => 1, 'lang' => 'eu', 'name' => 'Basque', 'direction' => 'ltr'],
        'be-tarask' => ['q' => 0.5, 'lang' => 'be-tarask', 'name' => 'Belarusian (Taraškievica orthography)', 'direction' => 'ltr'],
        'br'        => ['q' => 0.8, 'lang' => 'br', 'name' => 'Breton', 'direction' => 'ltr'],
        'bg'        => ['q' => 0.8, 'lang' => 'bg', 'name' => 'Bulgarian', 'direction' => 'ltr'],
        'my'        => ['q' => 1, 'lang' => 'my', 'name' => 'Burmese', 'direction' => 'ltr'],
        'ca'        => ['q' => 0.5, 'lang' => 'ca', 'name' => 'Catalan', 'direction' => 'ltr'],
        'zh-cn'     => ['q' => 0.9, 'lang' => 'zh_CN', 'name' => 'Chinese (Simplified)', 'direction' => 'ltr'],
        'zh-hant'   => ['q' => 0.2, 'lang' => 'zh_TW', 'name' => 'Chinese (Taiwanese)', 'direction' => 'ltr'],
        'ksh'       => ['q' => 1, 'lang' => 'ksh', 'name' => 'Colognian', 'direction' => 'ltr'],
        'cs'        => ['q' => 0.5, 'lang' => 'cs', 'name' => 'Czech', 'direction' => 'ltr'],
        'da'        => ['q' => 0.8, 'lang' => 'da', 'name' => 'Danish', 'direction' => 'ltr'],
        'nl'        => ['q' => 0.5, 'lang' => 'nl', 'name' => 'Dutch', 'direction' => 'ltr'],
        'arz'       => ['q' => 0.8, 'lang' => 'arz', 'name' => 'Egyptian Spoken Arabic', 'direction' => 'rtl'],
        'en'        => ['q' => 1, 'lang' => 'en', 'name' => 'English', 'direction' => 'ltr'],
        'en-us'     => ['q' => 1, 'lang' => 'en', 'name' => 'English (US)', 'direction' => 'ltr'],
        'en-gb'     => ['q' => 1, 'lang' => 'en_GB', 'name' => 'English (UK)', 'direction' => 'ltr'],
        'eo'        => ['q' => 0.8, 'lang' => 'eo', 'name' => 'Esperanto', 'direction' => 'ltr'],
        'fi'        => ['q' => 1, 'lang' => 'fi', 'name' => 'Finnish', 'direction' => 'ltr'],
        'fr'        => ['q' => 1, 'lang' => 'fr', 'name' => 'French', 'direction' => 'ltr'],
        'fr-fr'     => ['q' => 1, 'lang' => 'fr', 'name' => 'French (France)', 'direction' => 'ltr'],
        'fur'       => ['q' => 0.8, 'lang' => 'fur', 'name' => 'Friulian', 'direction' => 'ltr'],
        'gl'        => ['q' => 0.8, 'lang' => 'gl', 'name' => 'Galician', 'direction' => 'ltr'],
        'ka'        => ['q' => 0.8, 'lang' => 'ka', 'name' => 'Georgian', 'direction' => 'ltr'],
        'de'        => ['q' => 0.8, 'lang' => 'de', 'name' => 'German', 'direction' => 'ltr'],
        'el'        => ['q' => 0.1, 'lang' => 'el', 'name' => 'Greek', 'direction' => 'ltr'],
        'he'        => ['q' => 0.5, 'lang' => 'he', 'name' => 'Hebrew', 'direction' => 'rtl'],
        'hu'        => ['q' => 0.8, 'lang' => 'hu', 'name' => 'Hungarian', 'direction' => 'ltr'],
        'is'        => ['q' => 0.1, 'lang' => 'is', 'name' => 'Icelandic', 'direction' => 'ltr'],
        'id'        => ['q' => 1, 'lang' => 'id', 'name' => 'Indonesian', 'direction' => 'ltr'],
        'ia'        => ['q' => 0.8, 'lang' => 'ia', 'name' => 'Interlingua', 'direction' => 'ltr'],
        'ga'        => ['q' => 0.5, 'lang' => 'ga', 'name' => 'Irish', 'direction' => 'ltr'],
        'it'        => ['q' => 1, 'lang' => 'it', 'name' => 'Italian', 'direction' => 'ltr'],
        'ja'        => ['q' => 0.5, 'lang' => 'ja', 'name' => 'Japanese', 'direction' => 'ltr'],
        'ko'        => ['q' => 0.9, 'lang' => 'ko', 'name' => 'Korean', 'direction' => 'ltr'],
        'lv'        => ['q' => 1, 'lang' => 'lv', 'name' => 'Latvian', 'direction' => 'ltr'],
        'lt'        => ['q' => 1, 'lang' => 'lt', 'name' => 'Lithuanian', 'direction' => 'ltr'],
        'lb'        => ['q' => 1, 'lang' => 'lb', 'name' => 'Luxembourgish', 'direction' => 'ltr'],
        'mk'        => ['q' => 0.5, 'lang' => 'mk', 'name' => 'Macedonian', 'direction' => 'ltr'],
        'mg'        => ['q' => 1, 'lang' => 'mg', 'name' => 'Malagasy', 'direction' => 'ltr'],
        'ms'        => ['q' => 1, 'lang' => 'ms', 'name' => 'Malay', 'direction' => 'ltr'],
        'ml'        => ['q' => 0.5, 'lang' => 'ml', 'name' => 'Malayalam', 'direction' => 'ltr'],
        'ne'        => ['q' => 1, 'lang' => 'ne', 'name' => 'Nepali', 'direction' => 'ltr'],
        'nb'        => ['q' => 0.1, 'lang' => 'nb', 'name' => 'Norwegian (Bokmål)', 'direction' => 'ltr'],
        'no'        => ['q' => 0.1, 'lang' => 'nb', 'name' => 'Norwegian (Bokmål)', 'direction' => 'ltr'],
        'nn'        => ['q' => 1, 'lang' => 'nn', 'name' => 'Norwegian (Nynorsk)', 'direction' => 'ltr'],
        'fa'        => ['q' => 1, 'lang' => 'fa', 'name' => 'Persian', 'direction' => 'rtl'],
        'pl'        => ['q' => 0.5, 'lang' => 'pl', 'name' => 'Polish', 'direction' => 'ltr'],
        'pt'        => ['q' => 1, 'lang' => 'pt', 'name' => 'Portuguese', 'direction' => 'ltr'],
        'pt-br'     => ['q' => 0.9, 'lang' => 'pt_BR', 'name' => 'Brazilian Portuguese', 'direction' => 'ltr'],
        'ru'        => ['q' => 0.9, 'lang' => 'ru', 'name' => 'Russian', 'direction' => 'ltr'],
        'sr-ec'     => ['q' => 1, 'lang' => 'sr-ec', 'name' => 'Serbian', 'direction' => 'ltr'],
        'es'        => ['q' => 1, 'lang' => 'es', 'name' => 'Spanish', 'direction' => 'ltr'],
        'sv'        => ['q' => 0.8, 'lang' => 'sv', 'name' => 'Swedish', 'direction' => 'ltr'],
        'tl'        => ['q' => 0.8, 'lang' => 'tl', 'name' => 'Tagalog', 'direction' => 'ltr'],
        'ta'        => ['q' => 1, 'lang' => 'ta', 'name' => 'Tamil', 'direction' => 'ltr'],
        'te'        => ['q' => 0.3, 'lang' => 'te', 'name' => 'Telugu', 'direction' => 'ltr'],
        'tr'        => ['q' => 0.5, 'lang' => 'tr', 'name' => 'Turkish', 'direction' => 'ltr'],
        'uk'        => ['q' => 1, 'lang' => 'uk', 'name' => 'Ukrainian', 'direction' => 'ltr'],
        'hsb'       => ['q' => 0.8, 'lang' => 'hsb', 'name' => 'Upper Sorbian', 'direction' => 'ltr'],
        'ur'        => ['q' => 1, 'lang' => 'ur_PK', 'name' => 'Urdu (Pakistan)', 'direction' => 'rtl'],
        'vi'        => ['q' => 0.8, 'lang' => 'vi', 'name' => 'Vietnamese', 'direction' => 'ltr'],
    ];
}
