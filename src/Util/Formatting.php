<?php

// {{{ License
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
// }}}

/**
 * String formatting utilities
 *
 * @package   GNUsocial
 * @category  Util
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util;

use App\Core\Event;
use App\Core\Log;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\Group;
use App\Entity\Note;
use App\Util\Exception\NicknameException;
use App\Util\Exception\ServerException;
use Functional as F;
use InvalidArgumentException;

abstract class Formatting
{
    private static ?\Twig\Environment $twig;
    public static function setTwig(\Twig\Environment $twig)
    {
        self::$twig = $twig;
    }

    public static function twigRenderString(string $template, array $context): string
    {
        return self::$twig->createTemplate($template, null)->render($context);
    }

    public static function twigRenderFile(string $template_path, array $context): string
    {
        return self::$twig->render($template_path, $context);
    }

    /**
     * Normalize path by converting \ to /
     *
     * @param string $path
     *
     * @return string
     */
    public static function normalizePath(string $path): string
    {
        return preg_replace(',(/|\\\\)+,', '/', $path);
    }

    /**
     * Get plugin name from it's path, or null if not a plugin
     *
     * @param string $path
     *
     * @return null|string
     */
    public static function moduleFromPath(string $path): ?string
    {
        foreach (['/plugins/', '/components/'] as $mod_p) {
            $module = strpos($path, $mod_p);
            if ($module === false) {
                continue;
            }
            $cut  = $module + strlen($mod_p);
            $cut2 = strpos($path, '/', $cut);
            if ($cut2) {
                $final = substr($path, $cut, $cut2 - $cut);
            } else {
                // We might be running directly from the plugins dir?
                // If so, there's no place to store locale info.
                $m = 'The GNU social install dir seems to contain a piece named \'plugin\' or \'component\'';
                Log::critical($m);
                throw new ServerException($m);
            }
            return $final;
        }
        return null;
    }

    /**
     * Check whether $haystack starts with $needle
     *
     * @param array|string $haystack if array, check that all strings start with $needle
     * @param string       $needle
     *
     * @return bool
     */
    public static function startsWith($haystack, string $needle): bool
    {
        if (is_string($haystack)) {
            $length = strlen($needle);
            return substr($haystack, 0, $length) === $needle;
        }
        return F\every($haystack,
            function ($haystack) use ($needle) {
                return self::startsWith($haystack, $needle);
            });
    }

    /**
     * Check whether $haystack ends with $needle
     *
     * @param array|string $haystack if array, check that all strings end with $needle
     * @param string       $needle
     *
     * @return bool
     */
    public static function endsWith($haystack, string $needle)
    {
        if (is_string($haystack)) {
            $length = strlen($needle);
            if ($length == 0) {
                return true;
            }
            return substr($haystack, -$length) === $needle;
        }
        return F\every($haystack,
            function ($haystack) use ($needle) {
                return self::endsWith($haystack, $needle);
            });
    }

    /**
     * If $haystack starts with $needle, remove it from the beginning
     */
    public static function removePrefix(string $haystack, string $needle)
    {
        return self::startsWith($haystack, $needle) ? substr($haystack, strlen($needle)) : $haystack;
    }

    /**
     * If $haystack ends with $needle, remove it from the end
     */
    public static function removeSuffix(string $haystack, string $needle)
    {
        return self::endsWith($haystack, $needle) && !empty($needle) ? substr($haystack, 0, -strlen($needle)) : $haystack;
    }

    public static function camelCaseToSnakeCase(string $str): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $str));
    }

    public static function snakeCaseToCamelCase(string $str): string
    {
        return implode('', F\map(preg_split('/[\b_]/', $str), F\ary('ucfirst', 1)));
    }

    /**
     * Indent $in, a string or array, $level levels
     *
     * @param array|string $in
     * @param int          $level How many levels of indentation
     * @param int          $count How many spaces per indentation
     *
     * @return string
     */
    public static function indent($in, int $level = 1, int $count = 2): string
    {
        if (is_string($in)) {
            return self::indent(explode("\n", $in), $level, $count);
        } elseif (is_array($in)) {
            $indent = str_repeat(' ', $count * $level);
            return implode("\n", F\map(F\select($in,
                F\ary(function ($s) {
                    return $s != '';
                }, 1)),
                function ($val) use ($indent) {
                    return F\concat($indent . $val);
                }));
        }
        throw new InvalidArgumentException('Formatting::indent\'s first parameter must be either an array or a string. Input was: ' . $in);
    }

    const SPLIT_BY_SPACE = ' ';
    const JOIN_BY_SPACE  = ' ';
    const SPLIT_BY_COMMA = ', ';
    const JOIN_BY_COMMA  = ', ';
    const SPLIT_BY_BOTH  = '/[, ]/';

    /**
     * Convert scalars, objects implementing __toString or arrays to strings
     *
     * @param mixed $value
     */
    public static function toString($value, string $join_type = self::JOIN_BY_COMMA): string
    {
        if (!in_array($join_type, [static::JOIN_BY_SPACE, static::JOIN_BY_COMMA])) {
            throw new \Exception('Formatting::toString received invalid join option');
        } else {
            if (!is_array($value)) {
                return (string) $value;
            } else {
                return implode($join_type, $value);
            }
        }
    }

    /**
     * Convert a user supplied string to array and return whether the conversion was successfull
     *
     * @param mixed $output
     */
    public static function toArray(string $input, &$output, string $split_type = self::SPLIT_BY_COMMA): bool
    {
        if (!in_array($split_type, [static::SPLIT_BY_SPACE, static::SPLIT_BY_COMMA, static::SPLIT_BY_BOTH])) {
            throw new \Exception('Formatting::toArray received invalid split option');
        }
        if ($input == '') {
            $output = [];
            return true;
        }
        $matches = [];
        if (preg_match('/^ *\[?([^,]+(, ?[^,]+)*)\]? *$/', $input, $matches)) {
            switch ($split_type) {
            case self::SPLIT_BY_BOTH:
                $arr = preg_split($split_type, $matches[1], 0, PREG_SPLIT_NO_EMPTY);
                break;
            case self::SPLIT_BY_COMMA:
                $arr = preg_split('/, ?/', $matches[1]);
                break;
            default:
                $arr = explode($split_type[0], $matches[1]);
            }
            $output = str_replace([' \'', '\'', ' "', '"'], '', $arr);
            $output = F\map($output, F\ary('trim', 1));
            return true;
        }
        return false;
    }

    /**
     * Render a plain text note content into HTML, extracting links and tags
     */
    public static function renderPlainText(string $text): string
    {
        $text = self::removeUnicodeFormattingCodes($text);
        $text = nl2br(htmlspecialchars($text, flags: ENT_QUOTES | ENT_SUBSTITUTE, double_encode: false), use_xhtml: false);

        // Remove ASCII control codes
        $text = preg_replace('/[\x{0}-\x{8}\x{b}-\x{c}\x{e}-\x{19}]/', '', $text);
        $text = self::replaceURLs($text, [self::class, 'linkify']);
        $text = preg_replace_callback('/(^|\&quot\;|\'|\(|\[|\{|\s+)#([\pL\pN_\-\.]{1,64})/u',
                                      fn ($m) => "{$m[1]}#" . self::tagLink($m[2]), $text);

        return $text;
    }

    /**
     * Strip Unicode text formatting/direction codes. This is can be
     * pretty dangerous for visualisation of text or be used for
     * mischief
     */
    public static function removeUnicodeFormattingCodes(string $text): string
    {
        return preg_replace('/[\\x{200b}-\\x{200f}\\x{202a}-\\x{202e}]/u', '', $text);
    }

    const URL_SCHEME_COLON_DOUBLE_SLASH = 1;
    const URL_SCHEME_SINGLE_COLON       = 2;
    const URL_SCHEME_NO_DOMAIN          = 4;
    const URL_SCHEME_COLON_COORDINATES  = 8;

    public static function URLSchemes($filter = null)
    {
        // TODO: move these to config
        $schemes = [
            'http'     => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'https'    => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'ftp'      => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'ftps'     => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'mms'      => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'rtsp'     => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'gopher'   => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'news'     => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'nntp'     => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'telnet'   => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'wais'     => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'file'     => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'prospero' => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'webcal'   => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'irc'      => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'ircs'     => self::URL_SCHEME_COLON_DOUBLE_SLASH,
            'aim'      => self::URL_SCHEME_SINGLE_COLON,
            'bitcoin'  => self::URL_SCHEME_SINGLE_COLON,
            'fax'      => self::URL_SCHEME_SINGLE_COLON,
            'jabber'   => self::URL_SCHEME_SINGLE_COLON,
            'mailto'   => self::URL_SCHEME_SINGLE_COLON,
            'tel'      => self::URL_SCHEME_SINGLE_COLON,
            'xmpp'     => self::URL_SCHEME_SINGLE_COLON,
            'magnet'   => self::URL_SCHEME_NO_DOMAIN,
            'geo'      => self::URL_SCHEME_COLON_COORDINATES,
        ];

        return array_keys(array_filter($schemes, fn ($scheme) => is_null($filter) || ($scheme & $filter)));
    }

    /**
     * Find links in the given text and pass them to the given callback function.
     *
     * @param string          $text
     * @param callable(string $text, mixed $arg): string $callback: return replacement text
     * @param mixed           $arg:  optional argument will be passed on to the callback
     */
    public static function replaceURLs(string $text, callable $callback, mixed $arg = null)
    {
        $geouri_labeltext_regex   = '\pN\pL\-';
        $geouri_mark_regex        = '\-\_\.\!\~\*\\\'\(\)';    // the \\\' is really pretty
        $geouri_unreserved_regex  = '\pN\pL' . $geouri_mark_regex;
        $geouri_punreserved_regex = '\[\]\:\&\+\$';
        $geouri_pctencoded_regex  = '(?:\%[0-9a-fA-F][0-9a-fA-F])';
        $geouri_paramchar_regex   = $geouri_unreserved_regex . $geouri_punreserved_regex; //FIXME: add $geouri_pctencoded_regex here so it works

        // Start off with a regex
        $regex = '#' .
               '(?:^|[\s\<\>\(\)\[\]\{\}\\\'\\\";]+)(?![\@\!\#])' .
               '(' .
               '(?:' .
               '(?:' . //Known protocols
               '(?:' .
               '(?:(?:' . implode('|', self::URLSchemes(self::URL_SCHEME_COLON_DOUBLE_SLASH)) . ')://)' .
               '|' .
               '(?:(?:' . implode('|', self::URLSchemes(self::URL_SCHEME_SINGLE_COLON)) . '):)' .
               ')' .
               '(?:[\pN\pL\-\_\+\%\~]+(?::[\pN\pL\-\_\+\%\~]+)?\@)?' . //user:pass@
               '(?:' .
               '(?:' .
               '\[[\pN\pL\-\_\:\.]+(?<![\.\:])\]' . //[dns]
               ')|(?:' .
               '[\pN\pL\-\_\:\.]+(?<![\.\:])' . //dns
               ')' .
               ')' .
               ')' .
               '|(?:' .
               '(?:' . implode('|', self::URLSchemes(self::URL_SCHEME_COLON_COORDINATES)) . '):' .
               // There's an order that must be followed here too, if ;crs= is used, it must precede ;u=
               // Also 'crsp' (;crs=$crsp) must match $geouri_labeltext_regex
               // Also 'uval' (;u=$uval) must be a pnum: \-?[0-9]+
               '(?:' .
               '(?:[0-9]+(?:\.[0-9]+)?(?:\,[0-9]+(?:\.[0-9]+)?){1,2})' .    // 1(.23)?(,4(.56)){1,2}
               '(?:\;(?:[' . $geouri_labeltext_regex . ']+)(?:\=[' . $geouri_paramchar_regex . ']+)*)*' .
               ')' .
               ')' .
               // URLs without domain name, like magnet:?xt=...
               '|(?:(?:' . implode('|', self::URLSchemes(self::URL_SCHEME_NO_DOMAIN)) . '):(?=\?))' .  // zero-length lookahead requires ? after :
               (Common::config('linkify', 'ipv4')   // Convert IPv4 addresses to hyperlinks
                ? '|(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)'
                : '') .
               (Common::config('linkify', 'ipv6')   // Convert IPv6 addresses to hyperlinks
                ? '|(?:' . //IPv6
                '\[?(?:(?:(?:[0-9A-Fa-f]{1,4}:){7}(?:(?:[0-9A-Fa-f]{1,4})|:))|(?:(?:[0-9A-Fa-f]{1,4}:){6}(?::|(?:(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})(?:\.(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})|(?::[0-9A-Fa-f]{1,4})))|(?:(?:[0-9A-Fa-f]{1,4}:){5}(?:(?::(?:(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})(?:\.(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|(?:(?::[0-9A-Fa-f]{1,4}){1,2})))|(?:(?:[0-9A-Fa-f]{1,4}:){4}(?::[0-9A-Fa-f]{1,4}){0,1}(?:(?::(?:(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})(?:\.(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|(?:(?::[0-9A-Fa-f]{1,4}){1,2})))|(?:(?:[0-9A-Fa-f]{1,4}:){3}(?::[0-9A-Fa-f]{1,4}){0,2}(?:(?::(?:(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})(?:\.(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|(?:(?::[0-9A-Fa-f]{1,4}){1,2})))|(?:(?:[0-9A-Fa-f]{1,4}:){2}(?::[0-9A-Fa-f]{1,4}){0,3}(?:(?::(?:(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})(?:\.(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|(?:(?::[0-9A-Fa-f]{1,4}){1,2})))|(?:(?:[0-9A-Fa-f]{1,4}:)(?::[0-9A-Fa-f]{1,4}){0,4}(?:(?::(?:(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})(?:\.(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|(?:(?::[0-9A-Fa-f]{1,4}){1,2})))|(?::(?::[0-9A-Fa-f]{1,4}){0,5}(?:(?::(?:(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})(?:\.(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|(?:(?::[0-9A-Fa-f]{1,4}){1,2})))|(?:(?:(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})(?:\.(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})))\]?(?<!:)' .
                ')'
                : '') .
               (Common::config('linkify', 'bare_domains')
                ? '|(?:' . //DNS
                '(?:[\pN\pL\-\_\+\%\~]+(?:\:[\pN\pL\-\_\+\%\~]+)?\@)?' . //user:pass@
                '[\pN\pL\-\_]+(?:\.[\pN\pL\-\_]+)*\.' .
                //tld list from http://data.iana.org/TLD/tlds-alpha-by-domain.txt, also added local, loc, and onion
                '(?:AC|AD|AE|AERO|AF|AG|AI|AL|AM|AN|AO|AQ|AR|ARPA|AS|ASIA|AT|AU|AW|AX|AZ|BA|BB|BD|BE|BF|BG|BH|BI|BIZ|BJ|BM|BN|BO|BR|BS|BT|BV|BW|BY|BZ|CA|CAT|CC|CD|CF|CG|CH|CI|CK|CL|CM|CN|CO|COM|COOP|CR|CU|CV|CX|CY|CZ|DE|DJ|DK|DM|DO|DZ|EC|EDU|EE|EG|ER|ES|ET|EU|FI|FJ|FK|FM|FO|FR|GA|GB|GD|GE|GF|GG|GH|GI|GL|GM|GN|GOV|GP|GQ|GR|GS|GT|GU|GW|GY|HK|HM|HN|HR|HT|HU|ID|IE|IL|IM|IN|INFO|INT|IO|IQ|IR|IS|IT|JE|JM|JO|JOBS|JP|KE|KG|KH|KI|KM|KN|KP|KR|KW|KY|KZ|LA|LB|LC|LI|LK|LR|LS|LT|LU|LV|LY|MA|MC|MD|ME|MG|MH|MIL|MK|ML|MM|MN|MO|MOBI|MP|MQ|MR|MS|MT|MU|MUSEUM|MV|MW|MX|MY|MZ|NA|NAME|NC|NE|NET|NF|NG|NI|NL|NO|NP|NR|NU|NZ|OM|ORG|PA|PE|PF|PG|PH|PK|PL|PM|PN|PR|PRO|PS|PT|PW|PY|QA|RE|RO|RS|RU|RW|SA|SB|SC|SD|SE|SG|SH|SI|SJ|SK|SL|SM|SN|SO|SR|ST|SU|SV|SY|SZ|TC|TD|TEL|TF|TG|TH|TJ|TK|TL|TM|TN|TO|TP|TR|TRAVEL|TT|TV|TW|TZ|UA|UG|UK|US|UY|UZ|VA|VC|VE|VG|VI|VN|VU|WF|WS|XN--0ZWM56D|测试|XN--11B5BS3A9AJ6G|परीक्षा|XN--80AKHBYKNJ4F|испытание|XN--9T4B11YI5A|테스트|XN--DEBA0AD|טעסט|XN--G6W251D|測試|XN--HGBK6AJ7F53BBA|آزمایشی|XN--HLCJ6AYA9ESC7A|பரிட்சை|XN--JXALPDLP|δοκιμή|XN--KGBECHTV|إختبار|XN--ZCKZAH|テスト|YE|YT|YU|ZA|ZM|ZONE|ZW|local|loc|onion)' .
                ')(?![\pN\pL\-\_])'
                : '') . // if common_config('linkify', 'bare_domains') is false, don't add anything here
               ')' .
               '(?:' .
               '(?:\:\d+)?' . //:port
               '(?:/[' . URL_REGEX_VALID_PATH_CHARS . ']*)?' .  // path
               '(?:\?[' . URL_REGEX_VALID_QSTRING_CHARS . ']*)?' .  // ?query string
               '(?:\#[' . URL_REGEX_VALID_FRAGMENT_CHARS . ']*)?' . // #fragment
               ')(?<![' . URL_REGEX_EXCLUDED_END_CHARS . '])' .
               ')' .
               '#ixu';

        return preg_replace_callback($regex, fn ($matches) => self::callbackHelper($matches, $callback, $arg), $text);
    }

    /**
     * Intermediate callback for common_replace_links(), helps resolve some
     * ambiguous link forms before passing on to the final callback.
     *
     * @param array    $matches
     * @param callable $callback
     * @param mixed    $arg      optional argument to pass on as second param to callback
     *
     * @return string
     *
     */
    private static function callbackHelper(array $matches, callable $callback, mixed $arg = null): string
    {
        $url   = $matches[1];
        $left  = strpos($matches[0], $url);
        $right = $left + strlen($url);

        $groupSymbolSets = [
            [
                'left'  => '(',
                'right' => ')',
            ],
            [
                'left'  => '[',
                'right' => ']',
            ],
            [
                'left'  => '{',
                'right' => '}',
            ],
            [
                'left'  => '<',
                'right' => '>',
            ],
        ];

        $cannotEndWith = ['.', '?', ',', '#'];
        do {
            $original_url = $url;
            foreach ($groupSymbolSets as $groupSymbolSet) {
                if (substr($url, -1) == $groupSymbolSet['right']) {
                    $group_left_count  = substr_count($url, $groupSymbolSet['left']);
                    $group_right_count = substr_count($url, $groupSymbolSet['right']);
                    if ($group_left_count < $group_right_count) {
                        --$right;
                        $url = substr($url, 0, -1);
                    }
                }
            }
            if (in_array(substr($url, -1), $cannotEndWith)) {
                --$right;
                $url = substr($url, 0, -1);
            }
        } while ($original_url != $url);

        $result = call_user_func_array($callback, [$url, $arg]);
        return substr($matches[0], 0, $left) . $result . substr($matches[0], $right);
    }

    /**
     * Convert a plain text $url to HTML <a>
     */
    public static function linkify(string $url): string
    {
        // It comes in special'd, so we unspecial it before passing to the stringifying
        // functions
        $url = htmlspecialchars_decode($url);

        if (strpos($url, '@') !== false && strpos($url, ':') === false && ($email = filter_var($url, FILTER_VALIDATE_EMAIL)) !== false) {
            //url is an email address without the mailto: protocol
            $url = "mailto:{$email}";
        }

        $attrs = ['href' => $url, 'title' => $url];

        // TODO Check to see whether this is a known "attachment" URL.

        // Whether to nofollow
        $nf = Common::config('nofollow', 'external');
        if ($nf == 'never') {
            $attrs['rel'] = 'external';
        } else {
            $attrs['rel'] = 'noopener nofollow external noreferrer';
        }

        return HTML::html(['a' => ['attrs' => $attrs, $url]]);
    }

    public static function tagLink(string $tag): string
    {
        $canonical = self::canonicalTag($tag);
        $url       = Router::url('tag', ['tag' => $canonical]);
        return HTML::html(['span' => ['a' => ['attrs' => ['href' => $url, 'rel' => 'tag']]]]);
    }

    public static function canonicalTag(string $tag): string
    {
        return substr(self::slugify($tag), 0, 64);
    }

    /**
     * Convert $str to it's closest ASCII representation
     */
    public static function slugify(string $str): string
    {
        // php-intl is highly recommended...
        if (!function_exists('transliterator_transliterate')) {
            $str = preg_replace('/[^\pL\pN]/u', '', $str);
            $str = mb_convert_case($str, MB_CASE_LOWER, 'UTF-8');
            $str = substr($str, 0, 64);
            return $str;
        }
        $str = transliterator_transliterate('Any-Latin;' .                  // any charset to latin compatible
                                            'NFD;' .                        // decompose
                                            '[:Nonspacing Mark:] Remove;' . // remove nonspacing marks (accents etc.)
                                            'NFC;' .                        // composite again
                                            '[:Punctuation:] Remove;' .     // remove punctuation (.,¿? etc.)
                                            'Lower();' .                    // turn into lowercase
                                            'Latin-ASCII;',                 // get ASCII equivalents (ð to d for example)
                                            $str);
        return preg_replace('/[^\pL\pN]/u', '', $str);
    }

    /**
     * Find @-mentions in the given text, using the given notice object as context.
     * References will be resolved with common_relative_profile() against the user
     * who posted the notice.
     *
     * Note the return data format is internal, to be used for building links and
     * such. Should not be used directly; rather, call common_linkify_mentions().
     *
     * @param string $text
     * @param Actor  $actor  the Actor that is sending the current text
     * @param Note   $parent the Note this text is in reply to, if any
     *
     * @return array
     *
     */
    public static function findMentions(string $text, Actor $actor, Note $parent = null)
    {
        $mentions = [];
        if (Event::handle('StartFindMentions', [$actor, $text, &$mentions])) {
            // Get the context of the original notice, if any
            $origMentions = [];
            // Does it have a parent notice for context?
            if ($parent instanceof Note) {
                foreach ($parent->getAttentionProfiles() as $repliedTo) {
                    if (!$repliedTo->isPerson()) {
                        continue;
                    }
                    $origMentions[$repliedTo->getId()] = $repliedTo;
                }
            }

            $matches = self::findMentionsRaw($text, '@');

            foreach ($matches as $match) {
                try {
                    $nickname = Nickname::normalize($match[0], check_already_used: false);
                } catch (NicknameException $e) {
                    // Bogus match? Drop it.
                    continue;
                }

                // primarily mention the profiles mentioned in the parent
                $mention_found_in_origMentions = false;
                foreach ($origMentions as $origMentionsId => $origMention) {
                    if ($origMention->getNickname() == $nickname) {
                        $mention_found_in_origMentions = $origMention;
                        // don't mention same twice! the parent might have mentioned
                        // two users with same nickname on different instances
                        unset($origMentions[$origMentionsId]);
                        break;
                    }
                }

                // Try to get a profile for this nickname.
                // Start with parents mentions, then go to parents sender context
                if ($mention_found_in_origMentions) {
                    $mentioned = $mention_found_in_origMentions;
                } elseif ($parent instanceof Note && $parent->getActorNickname() === $nickname) {
                    $mentioned = $parent->getActor();
                } else {
                    // sets to null if no match
                    $mentioned = $actor->findRelativeActor($nickname);
                }

                if ($mentioned instanceof Actor) {
                    $url = $mentioned->getUri();    // prefer the URI as URL, if it is one.
                    if (!Common::isValidHttpUrl($url)) {
                        $url = $mentioned->getUrl();
                    }

                    $mention = [
                        'mentioned' => [$mentioned],
                        'type'      => 'mention',
                        'text'      => $match[0],
                        'position'  => $match[1],
                        'length'    => mb_strlen($match[0]),
                        'title'     => $mentioned->getFullname(),
                        'url'       => $url,
                    ];

                    $mentions[] = $mention;
                }
            }

            // TODO Tag subscriptions
            // @#tag => mention of all subscriptions tagged 'tag'
            // $tag_matches = [];
            // preg_match_all(
            //     '/' . Nickname::BEFORE_MENTIONS . '@#([\pL\pN_\-\.]{1,64})/',
            //     $text,
            //     $tag_matches,
            //     PREG_OFFSET_CAPTURE
            // );
            // foreach ($tag_matches[1] as $tag_match) {
            //     $tag   = self::canonicalTag($tag_match[0]);
            //     $plist = Profile_list::getByTaggerAndTag($actor->getID(), $tag);
            //     if (!$plist instanceof Profile_list || $plist->private) {
            //         continue;
            //     }
            //     $tagged = $actor->getTaggedSubscribers($tag);
            //     $url = common_local_url(
            //         'showprofiletag',
            //         ['nickname' => $actor->getNickname(), 'tag' => $tag]
            //     );
            //     $mentions[] = ['mentioned' => $tagged,
            //         'type'                 => 'list',
            //         'text'                 => $tag_match[0],
            //         'position'             => $tag_match[1],
            //         'length'               => mb_strlen($tag_match[0]),
            //         'url'                  => $url, ];
            // }

            $group_matches = self::findMentionsRaw($text, '!');
            foreach ($group_matches as $group_match) {
                $nickname = Nickname::normalize($group_match[0], check_already_used: false);
                $group    = Group::getFromNickname($nickname, $actor);

                if (!$group instanceof Group) {
                    continue;
                }

                $profile = $group->getActor();

                $mentions[] = [
                    'mentioned' => [$profile],
                    'type'      => 'group',
                    'text'      => $group_match[0],
                    'position'  => $group_match[1],
                    'length'    => mb_strlen($group_match[0]),
                    'url'       => $group->getUri(),
                    'title'     => $group->getFullname(),
                ];
            }

            Event::handle('EndFindMentions', [$actor, $text, &$mentions]);
        }

        return $mentions;
    }

    /**
     * Does the actual regex pulls to find @-mentions in text.
     * Should generally not be called directly; for use in common_find_mentions.
     *
     * @param string $text
     * @param string $preMention Character(s) that signals a mention ('@', '!'...)
     *
     * @return array of PCRE match arrays
     */
    private static function findMentionsRaw(string $text, string $preMention = '@'): array
    {
        $tmatches = [];
        preg_match_all(
            '/^T (' . Nickname::DISPLAY_FMT . ') /',
            $text,
            $tmatches,
            PREG_OFFSET_CAPTURE
        );

        $atmatches = [];
        // the regexp's "(?!\@)" makes sure it doesn't matches the single "@remote" in "@remote@server.com"
        preg_match_all(
            '/' . Nickname::BEFORE_MENTIONS . preg_quote($preMention, '/') . '(' . Nickname::DISPLAY_FMT . ')\b(?!\@)/',
            $text,
            $atmatches,
            PREG_OFFSET_CAPTURE
        );

        $matches = array_merge($tmatches[1], $atmatches[1]);
        return $matches;
    }

    /**
     * Finds @-mentions within the partially-rendered text section and
     * turns them into live links.
     *
     * Should generally not be called except from common_render_content().
     *
     * @param string $text   partially-rendered HTML
     * @param Actor  $author the Actor that is composing the current notice
     * @param Note   $parent the Note this is sent in reply to, if any
     *
     * @return string partially-rendered HTML
     */
    public static function linkifyMentions($text, Actor $author, ?Note $parent = null)
    {
        $mentions = self::findMentions($text, $author, $parent);

        // We need to go through in reverse order by position,
        // so our positions stay valid despite our fudging with the
        // string!

        $points = [];

        foreach ($mentions as $mention) {
            $points[$mention['position']] = $mention;
        }

        krsort($points);

        foreach ($points as $position => $mention) {
            $linkText = self::linkifyMentionArray($mention);

            $text = substr_replace($text, $linkText, $position, $mention['length']);
        }

        return $text;
    }

    public static function linkifyMentionArray(array $mention)
    {
        $output = null;

        if (Event::handle('StartLinkifyMention', [$mention, &$output])) {
            $attrs = [
                'href'  => $mention['url'],
                'class' => 'h-card u-url p-nickname ' . $mention['type'],
            ];

            if (!empty($mention['title'])) {
                $attrs['title'] = $mention['title'];
            }

            $output = HTML::html(['a' => ['attrs' => $attrs, $mention['text']]]);

            Event::handle('EndLinkifyMention', [$mention, &$output]);
        }

        return $output;
    }
}
