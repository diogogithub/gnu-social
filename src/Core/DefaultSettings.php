<?php

/*
 * This file is part of GNU social - https://www.gnu.org/software/social
 *
 * GNU social is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GNU social is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
 */

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

namespace App\Core;

abstract class DefaultSettings
{
    public static array $defaults;
    public static function setDefaults()
    {
        self::$defaults = ['site' => ['name' => 'Just another GNU social node',
                'nickname'                   => 'gnusocial',
                'wildcard'                   => null,
                'theme'                      => 'neo-gnu',
                'logfile'                    => null,
                'logdebug'                   => false,
                'logo'                       => null,
                'ssllogo'                    => null,
                'logperf'                    => false, // Enable to dump performance counters to syslog
                'logperf_detail'             => false, // Enable to dump every counter hit
                'fancy'                      => false,
                'locale_path'                => INSTALLDIR . '/locale',
                'language'                   => 'en',
                'langdetect'                 => true,
                'languages'                  => get_all_languages(),
                'email'                      => array_key_exists('SERVER_ADMIN', $_SERVER) ? $_SERVER['SERVER_ADMIN'] : null,
                'fakeaddressrecovery'        => true,
                'broughtby'                  => null,
                'timezone'                   => 'UTC',
                'broughtbyurl'               => null,
                'closed'                     => false,
                'inviteonly'                 => true,
                'private'                    => false,
                'ssl'                        => 'never',
                'sslproxy'                   => false, // set to true to force GNU social to think it is HTTPS (i.e. using reverse proxy to enable it)
                'sslserver'                  => null,
                'dupelimit'                  => 60,    // default for same person saying the same thing
                'textlimit'                  => 1000,  // in chars; 0 == no limit
                'indent'                     => true,
                'use_x_sendfile'             => false,
                'notice'                     => null,  // site wide notice text
                'build'                      => 1,     // build number, for code-dependent cache
            ],
                'security' => ['hash_algos' => ['sha1', 'sha256', 'sha512']],   // set to null for anything that hash_hmac() can handle (and is in hash_algos())
                'db'       => ['database'  => null, // must be set
                    'schema_location'      => INSTALLDIR . '/classes',
                    'class_location'       => INSTALLDIR . '/classes',
                    'require_prefix'       => 'classes/',
                    'class_prefix'         => '',
                    'mirror'               => null,
                    'utf8'                 => true,
                    'db_driver'            => 'DB',      // XXX: JanRain libs only work with DB
                    'disable_null_strings' => true,      // 'NULL' can be harmful
                    'quote_identifiers'    => true,
                    'type'                 => 'mysql',
                    'schemacheck'          => 'runtime', // 'runtime' or 'script'
                    'annotate_queries'     => false,     // true to add caller comments to queries, eg /* POST Notice::saveNew */
                    'log_queries'          => false,     // true to log all DB queries
                    'log_slow_queries'     => 0,         // if set, log queries taking over N seconds
                    'mysql_foreign_keys'   => false, ],  // if set, enables experimental foreign key support on MySQL
                'fix' => ['fancyurls'      => true,   // makes sure aliases in WebFinger etc. are not f'd by index.php/ URLs
                    'legacy_http'          => false,  // set this to true if you have upgraded your site from http=>https
                ],
                'log' => [
                    'debugtrace' => false,  // index.php handleError function, whether to include exception backtrace in log
                ],
                'syslog' => ['appname' => 'statusnet', // for syslog
                    'priority'         => 'debug',     // XXX: currently ignored
                    'facility'         => LOG_USER,
                ],
                'queue' => ['enabled'       => true,
                    'daemon'                => false, // Use queuedaemon. Default to false
                    'threads'               => null,  // an empty value here uses processor count to determine
                    'subsystem'             => 'db',  // default to database, or 'stomp'
                    'stomp_server'          => null,
                    'queue_basename'        => '/queue/statusnet/',
                    'control_channel'       => '/topic/statusnet/control', // broadcasts to all queue daemons
                    'stomp_username'        => null,
                    'stomp_password'        => null,
                    'stomp_persistent'      => true,  // keep items across queue server restart, if persistence is enabled
                    'stomp_transactions'    => true,  // use STOMP transactions to aid in detecting failures (supported by ActiveMQ, but not by all)
                    'stomp_acks'            => true,  // send acknowledgements after successful processing (supported by ActiveMQ, but not by all)
                    'stomp_manual_failover' => true,  // if multiple servers are listed, treat them as separate (enqueue on one randomly, listen on all)
                    'monitor'               => null,  // URL to monitor ping endpoint (work in progress)
                    'softlimit'             => '90%', // total size or % of memory_limit at which to restart queue threads gracefully
                    'spawndelay'            => 1,     // Wait at least N seconds between (re)spawns of child processes to avoid slamming the queue server with subscription startup
                    'debug_memory'          => false, // true to spit memory usage to log
                    'breakout'              => [],    // List queue specifiers to break out when using Stomp queue.
                    // Default will share all queues for all sites within each group.
                    // Specify as <group>/<queue> or <group>/<queue>/<site>,
                    // using nickname identifier as site.
                    //
                    // 'main/distrib' separate "distrib" queue covering all sites
                    // 'xmpp/xmppout/mysite' separate "xmppout" queue covering just 'mysite'
                    'max_retries'     => 10,    // drop messages after N failed attempts to process (Stomp)
                    'dead_letter_dir' => false, // set to directory to save dropped messages into (Stomp)
                ],
                'license' => ['type' => 'cc',  // can be 'cc', 'allrightsreserved', 'private'
                    'owner'          => null,  // can be name of content owner e.g. for enterprise
                    'url'            => 'https://creativecommons.org/licenses/by/3.0/',
                    'title'          => 'Creative Commons Attribution 3.0',
                    // 'image' => $_path . '/theme/licenses/cc_by_3.0_80x15.png',
                ],
                'mail' => ['backend' => 'mail',
                    'params'         => null,
                    'domain_check'   => true,
                ],
                'nickname' => ['blacklist' => [],
                    'featured'             => [],
                ],
                'profile' => ['banned' => [],
                    'biolimit'         => null,
                    'changenick'       => false,
                    'allowprivate'     => false,  // whether to allow setting stream to private ("only followers can read")
                    'backup'           => false,  // can cause DoS, so should be done via CLI
                    'restore'          => false,
                    'delete'           => false,
                    'move'             => true,
                ],
                'image'  => ['jpegquality' => 85],
                'avatar' => ['server' => null,
                    'dir'             => INSTALLDIR . '/file/avatar/',
                    // 'url_base' => $_path . '/avatar/',
                    'ssl'     => null,
                    'maxsize' => 300,
                ],
                'foaf'   => ['mbox_sha1sum' => false],
                'public' => ['localonly' => false,
                    'blacklist'          => [],
                    'autosource'         => [],
                ],
                'theme' => ['server' => null,
                    'dir'            => null,
                    'path'           => null,
                    'ssl'            => null,
                ],
                'usertheme' => ['linkcolor' => 'black',
                    'backgroundcolor'       => 'black',
                ],
                'theme_upload' => ['enabled' => extension_loaded('zip')],
                'javascript'   => ['server' => null,
                    'path'                  => null,
                    'ssl'                   => null,
                    'bustframes'            => true,
                ],
                'local' => // To override path/server for themes in 'local' dir (not currently applied to local plugins)
                    ['server'  => null,
                        'dir'  => null,
                        'path' => null,
                        'ssl'  => null,
                    ],
                'throttle' => ['enabled' => false, // whether to throttle edits; false by default
                    'count'              => 20,    // number of allowed messages in timespan
                    'timespan'           => 600,   // timespan for throttling
                ],
                'invite' => ['enabled' => true],
                'tag'    => ['dropoff' => 864000.0,   // controls weighting based on age
                    'cutoff'           => 86400 * 90, // only look at notices posted in last 90 days
                ],
                'popular' => ['dropoff' => 864000.0,   // controls weighting based on age
                    'cutoff'            => 86400 * 90, // only look at notices favorited in last 90 days
                ],
                'daemon' => ['piddir' => sys_get_temp_dir(),
                    'user'            => false,
                    'group'           => false,
                ],
                'emailpost'     => ['enabled' => false],
                'sms'           => ['enabled' => false],
                'twitterimport' => ['enabled' => false],
                'integration'   => ['source' => 'StatusNet', // source attribute for Twitter
                    'taguri'                 => null,        // base for tag URIs
                ],
                'twitter' => ['signin' => true,
                    'consumer_key'     => null,
                    'consumer_secret'  => null,
                ],
                'cache' => ['base' => null],
                'ping'  => ['notify' => [],
                    'timeout'        => 2,
                ],
                'inboxes' => ['enabled' => true], // ignored after 0.9.x
                'newuser' => ['default' => null,
                    'welcome'           => null,
                ],
                'linkify' => // "bare" below means "without schema", like domain.com vs. https://domain.com
                    ['bare_domains' => false, // convert domain.com to <a href="http://domain.com/" ...>domain.com</a> ?
                        'bare_ipv4' => false, // convert IPv4 addresses to hyperlinks?
                        'bare_ipv6' => false, // convert IPv6 addresses to hyperlinks?
                    ],
                'attachments' => ['server' => null,
                    'dir'                  => INSTALLDIR . '/file/',
                    // 'path'      => $_path . '/file/',
                    'sslserver' => null,
                    'sslpath'   => null,
                    'ssl'       => null,
                    'supported' => ['application/vnd.oasis.opendocument.chart'     => 'odc',
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
                        'image/svg+xml'                                            => 'svg', // No built-in constant
                        image_type_to_mime_type(IMAGETYPE_ICO)                     => image_type_to_extension(IMAGETYPE_ICO),
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
                    // 'file_quota'    => common_get_preferred_php_upload_limit(),
                    'user_quota'    => 50000000,
                    'monthly_quota' => 15000000,
                    'uploads'       => true,
                    'show_html'     => false,   // show (filtered) text/html attachments (and oEmbed HTML etc.). Doesn't affect AJAX calls.
                    'show_thumbs'   => true,    // show thumbnails in notice lists for uploaded images, and photos and videos linked remotely that provide oEmbed info
                    'process_links' => true,    // check linked resources for embeddable photos and videos; this will hit referenced external web sites when processing new messages.
                    'extblacklist'  => [],
                    'memory_limit'  => '1024M', // PHP's memory limit to use temporarily when handling images
                ],
                'thumbnail' => ['dir' => null,  // falls back to File::path('thumb') (equivalent to ['attachments']['dir'] .  '/thumb/')
                    'path'            => null,  // falls back to generating a URL with File::url('thumb/$filename') (equivalent to ['attachments']['path'] . '/thumb/')
                    'server'          => null,  // Only used if ['thumbnail']['path'] is NOT empty, and then it falls back to ['site']['server'], schema is decided from GNUsocial::useHTTPS()
                    'crop'            => false, // overridden to true if thumb height === null
                    'maxsize'         => 1000,  // thumbs with an edge larger than this will not be generated
                    'width'           => 450,
                    'height'          => 600,
                    'upscale'         => false,
                    'animated'        => false, // null="UseFileAsThumbnail", false="can use still frame". true requires ImageMagickPlugin
                ],
                'application' => ['desclimit' => null],
                'group'       => ['maxaliases' => 3,
                    'desclimit'                => null,
                    'addtag'                   => true,
                ],
                'peopletag' => ['maxtags' => 100,             // maximum number of tags a user can create.
                    'maxpeople'           => 500,             // maximum no. of people with the same tag by the same user
                    'allow_tagging'       => ['all' => true], // equivalent to array('local' => true, 'remote' => true)
                    'desclimit'           => null,
                ],
                'search'   => ['type' => 'like'],
                'sessions' => ['handle' => false, // whether to handle sessions ourselves
                    'debug'             => false, // debugging output for sessions
                    'gc_limit'          => 1000,  // max sessions to expire at a time
                ],
                'htmlfilter' => // remove tags from user/remotely generated HTML if they are === true
                    ['img'      => true,
                        'video' => true,
                        'audio' => true,
                    ],
                'htmlpurifier' => // configurable options for HTMLPurifier
                    ['Cache.DefinitionImpl'    => 'Serializer',
                        'Cache.SerializerPath' => implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'gnusocial']),
                    ],
                'notice' => ['contentlimit' => null,
                    'allowprivate'          => false, // whether to allow users to "check the padlock" to publish notices available for their subscribers.
                    'defaultscope'          => null,  // null means 1 if site/private, 0 otherwise
                    'hidespam'              => true,  // Whether to hide silenced users from timelines
                ],
                'message'  => ['contentlimit' => null],
                'location' => ['share' => 'user', // whether to share location; 'always', 'user', 'never'
                    'sharedefault'     => false, ],
                'logincommand' => ['disabled' => true],
                'plugins'      => ['core' => ['Activity' => [],
                    'ActivityModeration'                 => [],
                    'ActivityVerb'                       => [],
                    'ActivityVerbPost'                   => [],
                    'AuthCrypt'                          => [],
                    'Favorite'                           => [],
                    'HTMLPurifierSchemes'                => [],
                    'Share'                              => [],
                    'TheFreeNetwork'                     => [
                        'protocols' => ['ActivityPub' => 'Activitypub_profile', 'OStatus' => 'Ostatus_profile'],
                    ],
                ],
                    'default' => ['AccountManager' => [],
                        'AntiBrute'                => [],
                        'Blacklist'                => [],
                        'Bookmark'                 => [],
                        'ClientSideShorten'        => [],
                        'Cronish'                  => [],
                        'DefaultLayout'            => [],
                        'DirectionDetector'        => [],
                        'DirectMessage'            => [],
                        'Directory'                => [],
                        'EmailAuthentication'      => [],
                        'Embed'                    => [],
                        'Event'                    => [],
                        'LRDD'                     => [],
                        'Nodeinfo'                 => [],
                        'OpenID'                   => [],
                        'DBQueue'                  => [],
                        'OpportunisticQM'          => [],
                        'RemoteFollow'             => [],
                        'ActivityPub'              => [], // The order is important here (IT HAS TO COME BEFORE OSTATUS)
                        'OStatus'                  => [],
                        'Poll'                     => [],
                        'SimpleCaptcha'            => [],
                        'TagSub'                   => [],
                        'WebFinger'                => [],
                    ],
                    'locale_path' => false, // Set to a path to use *instead of* each plugin's own locale subdirectories
                    'server'      => null,
                    'sslserver'   => null,
                    'path'        => null,
                    'sslpath'     => null,
                ],
                'admin' => ['panels' => ['site', 'user', 'paths', 'access', 'sessions', 'sitenotice', 'license', 'plugins',
                ],
                ],
                'singleuser' => ['enabled' => false,
                    'nickname'             => null,
                ],
                'robotstxt' => ['crawldelay' => 0,
                    'disallow'               => ['main', 'settings', 'admin', 'search', 'message'],
                ],
                'api'      => ['realm' => null],
                'nofollow' => ['subscribers' => true,
                    'members'                => true,
                    'peopletag'              => true,
                    'external'               => 'sometimes', // Options: 'sometimes', 'never', default = 'sometimes'
                ],
                'url' => ['shortener' => 'internal',
                    'maxurllength'    => 100,
                    'maxnoticelength' => -1,
                ],
                'http' => // HTTP client settings when contacting other sites
                   ['ssl_cafile'           => false, // To enable SSL cert validation, point to a CA bundle (eg '/usr/lib/ssl/certs/ca-certificates.crt') (this activates "ssl_verify_peer")
                       'ssl_verify_host'   => true,  // HTTPRequest2 makes sure this is set to CURLOPT_SSL_VERIFYHOST==2 if using curl
                       'curl'              => false, // Use CURL backend for HTTP fetches if available. (If not, PHP's socket streams will be used.)
                       'connect_timeout'   => 5,
                       'timeout'           => (int) (ini_get('default_socket_timeout')),   // effectively should be this by default already, but this makes it more explicitly configurable for you users .)
                       'proxy_host'        => null,
                       'proxy_port'        => null,
                       'proxy_user'        => null,
                       'proxy_password'    => null,
                       'proxy_auth_scheme' => null,
                   ],
                'router'      => ['cache' => true],  // whether to cache the router object. Defaults to true, turn off for devel
                'discovery'   => ['cors' => false], // Allow Cross-Origin Resource Sharing for service discovery (host-meta, XRD, etc.)
                'performance' => ['high' => false], // disable some features for higher performance; default false
            ];

        if ($_ENV['APP_DEBUG']) {
            $config = DB::getRepository('\App\Entity\Config')->findAll();
            if (count($config) < count(self::$defaults)) {
                foreach (self::$defaults as $section => $def) {
                    foreach ($def as $setting => $value) {
                        if (!isset($config[$section][$setting])) {
                            $config[$section][$setting]
                                = DB::getReference('\App\Entity\Config', ['section' => $section, 'setting' => $setting]);
                            DB::persist($config[$section][$setting]->setValue(serialize($value)));
                        }
                    }
                }
                DB::flush();
            }
        }
    }
}
