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
 * Write the default settings to the database
 *
 * @package GNUsocial
 * @category DB
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core\DB;

use function App\Core\I18n\_m;
use App\Core\I18n\I18nHelper;
use App\Util\Common;

abstract class DefaultSettings
{
    public static array $defaults;
    public static function setDefaults()
    {
        self::$defaults = [
            'site' => [
                'name'                 => $_ENV['SOCIAL_SITENAME'] ?? 'Another social instance',
                'server'               => $_ENV['SOCIAL_DOMAIN'],
                'notice'               => null,  // site wide notice text
                'theme'                => 'default',
                'logo'                 => null,
                'language'             => 'en',
                'detect_language'      => true,
                'languages'            => I18nHelper::get_all_languages(),
                'email'                => $_ENV['SERVER_ADMIN'] ?? $_ENV['SOCIAL_ADMIN_EMAIL'] ?? null,
                'recovery_disclose'    => false, // Whether to not say that we found the email in the database, when asking for recovery
                'timezone'             => 'UTC',
                'brought_by'           => null,
                'brought_by_url'       => null,
                'closed'               => false,
                'register_type'        => 'public',
                'nickname'             => $_ENV['SOCIAL_ADMIN_NICK'],
                'ssl'                  => 'always',
                'ssl_proxy'            => false, // set to true to force GNU social to think it is HTTPS (i.e. using reverse proxy to enable it)
                'duplicate_time_limit' => 60,    // default for same person saying the same thing
                'text_limit'           => 1000,  // in chars; 0 == no limit
                'x_static_delivery'    => null,
            ],
            'security' => ['hash_algos' => ['sha1', 'sha256', 'sha512']],  // set to null for anything that hash_hmac() can handle (and is in hash_algos())
            'db'       => ['mirror' => null],                              // TODO implement
            'cache'    => [
                'notice_max_count' => 128,
            ],
            'avatar' => [
                'server'      => null,
                'ssl'         => null,
                'dir'         => INSTALLDIR . '/file/avatar/',
                'max_size_px' => 300,
            ],
            'javascript' => [
                'server' => null,
                'ssl'    => null,
            ],
            'attachments' => [
                'server'    => null,
                'ssl'       => null,
                'dir'       => INSTALLDIR . '/file/uploads/',
                'supported' => [
                    'application/vnd.oasis.opendocument.chart'                 => 'odc',
                    'application/vnd.oasis.opendocument.formula'               => 'odf',
                    'application/vnd.oasis.opendocument.graphics'              => 'odg',
                    'application/vnd.oasis.opendocument.graphics-template'     => 'otg',
                    'application/vnd.oasis.opendocument.image'                 => 'odi',
                    'application/vnd.oasis.opendocument.presentation'          => 'odp',
                    'application/vnd.oasis.opendocument.presentation-template' => 'otp',
                    'application/vnd.oasis.opendocument.spreadsheet'           => 'ods',
                    'application/vnd.oasis.opendocument.spreadsheet-template'  => 'ots',
                    'application/vnd.oasis.opendocument.text'                  => 'odt',
                    'application/vnd.oasis.opendocument.text-master'           => 'odm',
                    'application/vnd.oasis.opendocument.text-template'         => 'ott',
                    'application/vnd.oasis.opendocument.text-web'              => 'oth',
                    'application/pdf'                                          => 'pdf',
                    'application/zip'                                          => 'zip',
                    'application/x-bzip2'                                      => 'bz2',
                    'application/x-go-sgf'                                     => 'sgf',
                    'application/xml'                                          => 'xml',
                    'application/gpx+xml'                                      => 'gpx',
                    image_type_to_mime_type(IMAGETYPE_PNG)                     => image_type_to_extension(IMAGETYPE_PNG),
                    image_type_to_mime_type(IMAGETYPE_JPEG)                    => image_type_to_extension(IMAGETYPE_JPEG),
                    image_type_to_mime_type(IMAGETYPE_GIF)                     => image_type_to_extension(IMAGETYPE_GIF),
                    image_type_to_mime_type(IMAGETYPE_ICO)                     => image_type_to_extension(IMAGETYPE_ICO),
                    'image/svg+xml'                                            => 'svg', // No built-in constant
                    'audio/ogg'                                                => 'ogg',
                    'audio/mpeg'                                               => 'mpg',
                    'audio/x-speex'                                            => 'spx',
                    'application/ogg'                                          => 'ogx',
                    'text/plain'                                               => 'txt',
                    'video/mpeg'                                               => 'mpeg',
                    'video/mp4'                                                => 'mp4',
                    'video/ogg'                                                => 'ogv',
                    'video/quicktime'                                          => 'mov',
                    'video/webm'                                               => 'webm',
                ],
                'file_quota'    => Common::get_preferred_php_upload_limit(),
                'user_quota'    => Common::size_str_to_int('200M'),
                'monthly_quota' => Common::size_str_to_int('20M'),
                'uploads'       => true,
                'show_html'     => true,    // show (filtered) text/html attachments (and oEmbed HTML etc.). Doesn't affect AJAX calls.
                'show_thumbs'   => true,    // show thumbnails in notice lists for uploaded images, and photos and videos linked remotely that provide oEmbed info
                'process_links' => true,    // check linked resources for embeddable photos and videos; this will hit referenced external web sites when processing new messages.
                'ext_blacklist' => [],
                'memory_limit'  => '1024M', // PHP memory limit to use temporarily when handling images
            ],
            'thumbnail' => [
                'server'      => null,
                'ssl'         => null,
                'dir'         => INSTALLDIR . '/file/thumbnails/',  // falls back to File::path('thumb') (equivalent to ['attachments']['dir'] .  '/thumb/')
                'crop'        => false, // overridden to true if thumb height === null
                'max_size_px' => 1000,  // thumbs with an edge larger than this will not be generated
                'width'       => 450,
                'height'      => 600,
                'upscale'     => false,
                'animated'    => false, // null="UseFileAsThumbnail", false="can use still frame". true="allow animated"
            ],
            'theme' => [
                'server' => null,
                'ssl'    => null,
                'dir'    => INSTALLDIR . '/public/theme/',
            ],
            'plugins' => [
                'server'      => null,
                'ssl'         => null,
                'core'        => [],
                'default'     => [],
                'locale_path' => null, // Set to a path to use *instead of* each plugin's own locale subdirectories
            ],
            'license' => [
                'type'  => 'cc',  // can be 'cc', 'allrightsreserved', 'private'
                'owner' => null,  // can be name of content owner e.g. for enterprise
                'url'   => 'https://creativecommons.org/licenses/by/4.0/',
                'title' => 'Creative Commons Attribution 4.0',
                'image' => '/theme/licenses/cc_by_4.0.png',
            ],
            'nickname' => [
                'blacklist' => ['doc', 'main', 'avatar', 'theme'],
                'featured'  => [],
            ],
            'profile' => [
                'bio_text_limit'       => null,
                'allow_nick_change'    => false,
                'allow_private_stream' => true,  // whether to allow setting stream to private ("only followers can read")
                'backup'               => false, // can cause DoS, so should be done via CLI
                'restore'              => false,
                'delete'               => false,
                'move'                 => false,
            ],
            'image'  => ['jpegquality' => 85],
            'foaf'   => ['mbox_sha1sum' => false],
            'public' => [
                'local_only'      => false,
                'blacklist'       => [],
                'exclude_sources' => [],
            ],
            'invite' => ['enabled' => true],
            'tag'    => [
                'dropoff' => 86400 * 10, // controls weighting based on age
                'cutoff'  => 86400 * 90, // only look at notices posted in last 90 days
            ],
            'popular' => [
                'dropoff' => 86400 * 10, // controls weighting based on age
                'cutoff'  => 86400 * 90, // only look at notices favorited in last 90 days
            ],
            'new_users' => [
                'default_subscriptions' => null,
                'welcome_user'          => null,
            ],
            'linkify' => [               // "bare" below means "without schema", like domain.com vs. https://domain.com
                'bare_domains' => false, // convert domain.com to <a href="http://domain.com/" ...>domain.com</a> ?
                'ipv4'         => false, // convert IPv4 addresses to hyperlinks?
                'ipv6'         => false, // convert IPv6 addresses to hyperlinks?
            ],
            'group' => [
                'max_aliases'       => 3,
                'description_limit' => null,
            ],
            'people_tag' => [
                'max_tags'          => 100,  // maximum number of tags a user can create.
                'max_people'        => 500,  // maximum no. of people with the same tag by the same user
                'allow_tagging'     => ['local' => true, 'remote' => true],
                'description_limit' => null,
            ],
            'search'      => ['type' => 'like'],
            'html_filter' => ['tags' => ['img', 'video', 'audio', 'script']],
            'notice'      => [
                'content_limit' => null,
                'allow_private' => false, // whether to allow users to "check the padlock" to publish notices available for their subscribers.
                'hide_banned'   => true,  // whether to hide silenced users from timelines
            ],
            'message'    => ['content_limit' => null],
            'location'   => ['share' => 'user'],
            'robots_txt' => [
                'crawl_delay' => 0,
                'disallow'    => ['main', 'settings', 'admin', 'search', 'message'],
            ],
            'nofollow' => [
                'subscribers' => true,
                'members'     => true,
                'peopletag'   => true,
                'external'    => 'sometimes', // Options: 'sometimes', 'never', default = 'sometimes'
            ],
            'url_shortener' => [
                'service'           => 'internal',
                'max_url_length'    => 100,
                'max_notice_length' => null,
            ],
            'http' => [ // HTTP client settings when contacting other sites
                'ssl_ca_file'       => '/docker/certbot/files/live/',
                'timeout'           => (int) (ini_get('default_socket_timeout')),   // effectively should be this by default already, but this makes it more explicitly configurable for you users .)
                'proxy_host'        => null,
                'proxy_port'        => null,
                'proxy_user'        => null,
                'proxy_password'    => null,
                'proxy_auth_scheme' => null,
            ],
            'discovery' => ['CORS' => false], // Allow Cross-Origin Resource Sharing for service discovery (host-meta, XRD, etc.)
        ];

        self::loadDefaults($_ENV['APP_ENV'] == 'prod');
    }

    public static function loadDefaults(bool $optimize = false)
    {
        if ($optimize || !isset($_ENV['HTTPS']) || !isset($_ENV['HTTP_HOST'])) {
            return;
        }

        // In dev mode, delete everything and reinsert, in case
        // defaults changed
        if ($_ENV['APP_ENV'] === 'dev' && !isset($_ENV['SOCIAL_NO_RELOAD_DEFAULTS'])) {
            DB::getConnection()->executeQuery('delete from config;');
        }

        // So, since not all DBMSs support multi row inserts, doctrine
        // doesn't implement it. The difference between this and the
        // normal version is that that one does 221 queries in 30 to
        // 50ms, while this does 2 in 10 to 15 ms.
        if (DB::getRepository('\App\Entity\Config')->count([]) == 0) {
            $sql = 'insert into config (section, setting, value) values';
            foreach (self::$defaults as $section => $def) {
                foreach ($def as $setting => $value) {
                    $v = serialize($value);
                    $sql .= " ('{$section}', '{$setting}', '{$v}'),";
                }
            }
            $sql = preg_replace('/,$/', ';', $sql);
            DB::getConnection()->executeQuery($sql);
        }
    }

    public static function _m_dynamic(): array
    {
        self::setDefaults();
        $m           = [];
        $m['domain'] = 'core';
        foreach (self::$defaults as $key => $inner) {
            $m[] = _m($key);
            foreach (array_keys($inner) as $inner_key) {
                $m[] = _m($inner_key);
            }
        }
        return $m;
    }
}
