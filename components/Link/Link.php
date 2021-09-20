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

namespace Component\Link;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Component;
use App\Entity;
use App\Entity\Note;
use App\Entity\NoteToLink;
use App\Util\Common;
use App\Util\HTML;
use InvalidArgumentException;

class Link extends Component
{
    /**
     * Extract URLs from $content and create the appropriate Link and NoteToLink entities
     */
    public function onProcessNoteContent(Note $note, string $content)
    {
        if (Common::config('attachments', 'process_links')) {
            $matched_urls = [];
            preg_match($this->getURLRegex(), $content, $matched_urls);
            $matched_urls = array_unique($matched_urls);
            foreach ($matched_urls as $match) {
                try {
                    $link_id = Entity\Link::getOrCreate($match)->getId();
                    DB::persist(NoteToLink::create(['link_id' => $link_id, 'note_id' => $note->getId()]));
                } catch (InvalidArgumentException) {
                    continue;
                }
            }
        }
        return Event::next;
    }

    public function onRenderContent(string &$text)
    {
        $text = $this->replaceURLs($text);
    }

    public function getURLRegex(): string
    {
        $geouri_labeltext_regex   = '\pN\pL\-';
        $geouri_mark_regex        = '\-\_\.\!\~\*\\\'\(\)';    // the \\\' is really pretty
        $geouri_unreserved_regex  = '\pN\pL' . $geouri_mark_regex;
        $geouri_punreserved_regex = '\[\]\:\&\+\$';
        $geouri_pctencoded_regex  = '(?:\%[0-9a-fA-F][0-9a-fA-F])';
        $geouri_paramchar_regex   = $geouri_unreserved_regex . $geouri_punreserved_regex; //FIXME: add $geouri_pctencoded_regex here so it works

        return '#' .
                   '(?:^|[\s\<\>\(\)\[\]\{\}\\\'\\\";]+)(?![\@\!\#])' .
                   '(' .
                   '(?:' .
                   '(?:' . //Known protocols
                   '(?:' .
                   '(?:(?:' . implode('|', $this->URLSchemes(self::URL_SCHEME_COLON_DOUBLE_SLASH)) . ')://)' .
                   '|' .
                   '(?:(?:' . implode('|', $this->URLSchemes(self::URL_SCHEME_SINGLE_COLON)) . '):)' .
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
                   '(?:' . implode('|', $this->URLSchemes(self::URL_SCHEME_COLON_COORDINATES)) . '):' .
                   // There's an order that must be followed here too, if ;crs= is used, it must precede ;u=
                   // Also 'crsp' (;crs=$crsp) must match $geouri_labeltext_regex
                   // Also 'uval' (;u=$uval) must be a pnum: \-?[0-9]+
                   '(?:' .
                   '(?:[0-9]+(?:\.[0-9]+)?(?:\,[0-9]+(?:\.[0-9]+)?){1,2})' .    // 1(.23)?(,4(.56)){1,2}
                   '(?:\;(?:[' . $geouri_labeltext_regex . ']+)(?:\=[' . $geouri_paramchar_regex . ']+)*)*' .
                   ')' .
                   ')' .
                   // URLs without domain name, like magnet:?xt=...
                   '|(?:(?:' . implode('|', $this->URLSchemes(self::URL_SCHEME_NO_DOMAIN)) . '):(?=\?))' .  // zero-length lookahead requires ? after :
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
    }

    const URL_SCHEME_COLON_DOUBLE_SLASH = 1;
    const URL_SCHEME_SINGLE_COLON       = 2;
    const URL_SCHEME_NO_DOMAIN          = 4;
    const URL_SCHEME_COLON_COORDINATES  = 8;

    public function URLSchemes($filter = null)
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
     * @param string $text
     */
    public function replaceURLs(string $text): string
    {
        $regex = $this->getURLRegex();
        return preg_replace_callback($regex, fn ($matches) => $this->callbackHelper($matches, [$this, 'linkify']), $text);
    }

    /**
     * Intermediate callback for `replaceURLs()`, which helps resolve some
     * ambiguous link forms before passing on to the final callback.
     *
     * @param array           $matches
     * @param callable(string $text):  string $callback: return replacement text
     *
     * @return string
     */
    private function callbackHelper(array $matches, callable $callback): string
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

        $result = $callback($url);
        return substr($matches[0], 0, $left) . $result . substr($matches[0], $right);
    }

    /**
     * Convert a plain text $url to HTML <a>
     */
    public function linkify(string $url): string
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

        return HTML::html(['a' => ['attrs' => $attrs, $url]], options: ['indent' => false]);
    }
}
