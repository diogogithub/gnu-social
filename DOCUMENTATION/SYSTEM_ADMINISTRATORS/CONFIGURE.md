Configuration options
================================================================================

The configuration for GNU social is stored in the database table
`config`. 

A Web based configuration panel exists so the site admin can configure
GNU social. The preferred method for changing config options is to use this
panel.

A command-line script, `set_config.php`, can be used to set individual
configuration options. It's in the `bin/` directory.

Almost all configuration options are made through a two-dimensional
associative array, cleverly named `$config`. A typical configuration
line will be:

    $config['section']['setting] = value;

The following documentation describes each section and setting.


site
-------------------------------------------------------------------------------

This section is a catch-all for site-wide variables.

* `name` (string, required, defaults to the value provided in the configre script,
    sitename): the name of your site, like 'YourCompany Microblog'.

* `server` (string, required, defaults to the value provided in the configre script,
    sitename): the server domain, like 'example.net'.

* `notice` (string, default null): A plain string that will appear on every page. A good
    place to put introductory information about your service, or info about upgrades and
    outages, or other community info. Any HTML will be escaped.

* `theme` (string, default 'default'): Theme for your site (see Theme section).
        
* `logo` (string, default null): URL of an image file to use as the logo for the site.
    Overrides the logo in the theme, if any.
    
* `language` (string, default "en"): default language for your site. Defaults to English.
    Note that this is overridden, if enabled in the following setting, if a user is logged
    in and has selected a different language or if the user is NOT logged in, but their
    browser requests a different langauge. Since pretty much everybody's browser requests
    a language, that means that changing this setting has little or no effect in practice.

* `detect_language` (boolean, default true): whether to use the most appropriate language
    depending on the requester's browser preferences.

* `languages` (array, default null): A list of languages supported on your site. Typically
    you'd only change this if you wanted to disable support for one or more languages:

    `unset($config['site']['languages']['de'])` will disable support for German.
    
* `email` (string, required): contact email address for your site. By default, it's
    extracted from your Web server environment or the value provided in the configure
    script; you may want to customize it.

* `recovery_disclose` (boolean, default false): whether to confirm if the email exists
    when attempting to login. Recommended to keep it false, for some privacy.

* `timezone` (string, default 'UTC'): default timezone for message display. Users
    can set their own time zone. Defaults to 'UTC', which is a pretty good
    default.

* `brought_by` (string, default null): text used for the "brought by" link.

* `brought_by_url` (string, default null): name of an organization or individual who
    provides the service. Each page will include a link to this name in the footer or
    sidebar. A good way to link to the blog, forum, wiki, corporate portal, or whoever is
    making the service available.
    
* `closed` (boolean, default false): If set to 'true', will disallow registration on your
    site. This is a easy way to restrict accounts to only one individual or group; just
    register the accounts you want on the service, *then* set this variable to 'true'.

* `invite_only` (boolean, default false): If set to 'true', will only allow registration
    if the user was invited by an existing user.

* `private` (boolean, default false): If set to 'true', anonymous users will be redirected
    to the 'login' page. Also, API methods that normally require no authentication will
    require it. Note that this does not turn off registration; use 'closed' or
    'invite_only' for that behaviour.

* `ssl` (enum['always', 'sometimes', 'never'], default always'): Whether to use SSL and
    https:// URLs for some or all pages.

    Possible values are 'always' (use it for all pages), 'never' (don't use it for any
    pages), or 'sometimes' (use it for sensitive pages that include passwords like login
    and registration, but not for regular pages).

* `ssl_proxy` (string|boolean, default false): Whether to force GNUsocial to think it is
    HTTPS when the server gives no such information. I.e. when you're using a reverse
    proxy that adds the encryption layer but the webserver that runs PHP isn't configured
    with a key and certificate. If a string is given, it will be used as the URL of the
    proxy server.

* `duplicate_time_limit` (integer, default 60): minimum time allowed for one person to say
    the same thing twice. Default 60s. If it happens faster than this, it's considered a
    user or UI error.

* `text_limit` (integer, default 1000): default max size for texts in the site. Can be
    fine-tuned for notices, messages, profile bios and group descriptions. Zero indicates
    no limit.

* `x-static-delivery` (string, default null): when a string, use this as the header with
    which to serve static files. Possible values are 'X-Sendfile' (for Apache and others)
    and 'X-Accel-Redirect' (for nginx).


security
-------------------------------------------------------------------------------

* `hash_algos` (array, default ['sha1', 'sha256', 'sha512']): set to null for anything
    that `hash_hmac()` can handle; can be any combination of the result of `hash_algos()`


db
-------------------------------------------------------------------------------

* `mirror` (array, default null): you can set this to an array of database connection
    URIs. If it's set, load will be split among these, and replication will be enabled.


fix
-------------------------------------------------------------------------------

* `fancy_urls` (boolean, default true): fix any non-facy url to the correct form, when
    possible.

* `http` (boolean, default true): fixe any http links to https.


queue
-------------------------------------------------------------------------------

You can configure the software to queue time-consuming tasks, like
sending out SMS, email or XMPP messages, for off-line processing.

* `enabled` (boolean, default true): Whether to uses queues.

* `daemon` (boolean, default false): Whether to use queuedaemon. False means
    you'll use OpportunisticQM plugin.

* `threads` (int): How many queue processes to run. Defaults to number of cpu cores in
    unix-like systems or 1 on other OSes.
    
* `subsystem` (enum["db", "stomp", "redis"], default 'db'): Which kind of
    queueserver to use. Values include "db" for our database queuing (no other server
    required), "stomp" for a stomp server amd "redis" for a Redis server.

* `basename` (string, default '/queue/gnusocial/'): a root name to use for queues (stomp
    and redis only). Typically something like '/queue/sitename/' makes sense. If running
    multiple instances on the same server, make sure that either this setting or
    `$config['site']['nickname']` are unique for each site to keep them separate.

* `control_channel` (string, default '/topic/gnusocial/control'): the control channel used
    for different queue processes to communicate.

* `monitor` (string, default null): URL endpoint to monitor queue status

* `soft_limit` (string, default '90%'): an absolute or relative "soft memory limit";
    daemons will restart themselves gracefully when they find they've hit this amount of
    memory usage. Relative means a percentage of PHP's global `memory_limit` setting.

* `spawn_delay` (integer, default 1): seconds to wait between deamon restarts.

* `debug_memory` (boolean, default false): log daemon's memory usage.

* `stomp_server` (string, default null): URI for stomp server. Something like
    "tcp://hostname:61613". More complicated ones are possible; see your stomp server's
    documentation for details.

* `stomp_username` (string, default null): username for connecting to the stomp server.

* `stomp_password` (string, default null): password for connecting to the stomp server.

* `stomp_persistent` (boolean, default true): Keep items across queue server restart, if
    enabled. Note: Under ActiveMQ, the server configuration determines if and how
    persistent storage is actually saved. Not all stomp servers support persistence.

* `stomp_transactions` (boolean, default true): use transactions to aid in error
    detection. A broken transaction will be seen quickly, allowing a message to be
    redelivered immediately if a daemon crashes. Not all stop servers support
    transactions.

* `stomp_acks` (boolean, default true): send acknowledgements to aid in flow control. An
    acknowledgement of successful processing tells the server we're ready for more and can
    help keep things moving smoothly. This should *not* be turned off when running with
    ActiveMQ, (it breaks if you do), but if using another message queue server that does
    not support acknowledgements you might need to disable this.

* `stomp_manual_failover` (boolean, default true): if multiple servers are listed, treat
    them as separate (enqueue on one randomly, listen on all).

* `max_retries` (integer, default 10): for stomp, drop messages after N failed
    attempts to process.

* `dead_letter_dir` (string, default null): for stomp, optional directory to dump
    data on failed queue processing events after discarding them.


avatar
-------------------------------------------------------------------------------

* `server` (string, default null): If set, defines another server where avatars are
    stored. Note that the `dir` still has to be writeable. You'd typically use this to
    split HTTP requests on the client to speed up page loading, either with another
    virtual server or with an NFS or SAMBA share. Clients typically only make 2
    connections to a single server at a time
    <https://www.w3.org/Protocols/rfc2616/rfc2616-sec8.html#sec8.1.4>, so this can
    parallelize the job.
    
* `url_base` (string, 'default '/avatar/'): URL where avatars can be found.

* `ssl` (boolean, default null): Whether to access avatars using HTTPS. Defaults
    to null, meaning to guess based on site-wide SSL settings.

* `dir` (string, default 'file/avatar/'): Directory to save avatar files to. 
    
* `max_size_px` (integer, default 300): Maximum width or height for user avatars, in pixels


javascript
-------------------------------------------------------------------------------

* `server` (string, default null): You can speed up page loading by pointing the
    javascript file lookup to another server (virtual or real). Defaults to NULL, meaning
    to use the site server.

* `url_base` (string default '/js/'): URL part for JavaScript files.

* `ssl` (boolean, default null): Whether to use SSL for JavaScript files. Default is null,
    which means guess based on site SSL settings.

* `bust_frames` (boolean, default true): If true, all web pages will break out of
    framesets. If false, can comfortably live in a frame or iframe... probably.


attachments
-------------------------------------------------------------------------------

* `server` (string, default null): Server name to use when creating URLs for uploaded
    files. Defaults to null, meaning to use the default Web server. Using a virtual server
    here can speed up Web performance.

* `url_base` (string, default '/file/'): URL path, relative to the server, to find
    files. Defaults to main path + '/file/'.

* `ssl` (boolean, default null): Whether to use HTTPS for file URLs. Defaults to null,
    meaning to use other SSL settings.

* `dir` (string, default '/file/uploads/'): Directory accessible to the Web process where
    uploads should go.

* `supported` (array): An associative array of mime types you accept to store and
    distribute, like 'image/gif', 'video/mpeg', 'audio/mpeg', to the corresponding file
    extension. Make sure you setup your server to properly recognize the types you want to
    support. It's important to use the result of calling `image_type_to_extension` for the
    appropriate image type, in the case of images. This is so all parts of the code see
    the same file extension for each image type (jpg vs jpeg). For example, to enable BMP
    uploads, add this to the config.php file: 
    `image_type_to_mime_type(IMAGETYPE_BMP) => image_type_to_extension(IMAGETYPE_BMP);` See
    https://www.php.net/manual/en/function.image-type-to-mime-type.php for a list of such
    constants. If a filetype is not listed there, it's possible to add the mimetype and
    the extension by hand, but they need to match those returned by the file command.

For quotas, be sure you've set the `upload_max_filesize` and `post_max_size` in php.ini to
be large enough to handle your upload. In httpd.conf (if you're using apache), check that
the LimitRequestBody directive isn't set too low (it's optional, so it may not be there at
all).

* `file_quota` (integer, defaults to minimum of `'post_max_size', 'upload_max_filesize',
    'memory_limit'`): Maximum size for a single file upload, in bytes. A user can send any
    amount of notices with attachments as long as each attachment is smaller than
    file_quota.

* `user_quota` (integer, default 200M): Total size, in bytes, a user can store on this
    server. Each user can store any number of files as long as their total size does not
    exceed the user_quota.

* `monthly_quota` (integer, default 20M): Total size in bytes that a user can upload each
    month.

* `uploads` (boolean, default true): Whether to allow uploading files with notices.

* `show_html` (boolean, default true): Whether to show (filtered) text/html attachments
    (and oEmbed HTML etc.). Doesn't affect AJAX calls.

* `show_thumbs` (boolean, default true): Whether to show thumbnails in notice lists for
    uploaded images, and photos and videos linked remotely that provide oEmbed info.

* `process_links` (boolean, default true): Whether to follow redirects and save all
    available file information (mimetype, date, size, oembed, etc.).

* `ext_blacklist` (array, default []): associative array to either deny certain extensions or
    change them to a different one. For example:

        $config['attachments']['extblacklist']['php'] = 'phps';  // this turns .php into .phps
        $config['attachments']['extblacklist']['exe'] = false;   // this would deny any uploads
                                                                 // of files with the "exe" extension

* `filename` (string, default hash): Name for new files, one of: 'upload', 'hash'.

* `memory_limit` (string, default '1024M'): PHP memory limit to use temporarily when
    handling images


thumbnail
-------------------------------------------------------------------------------

* `server` (string, default null): Server name from which to serve thumbnails. Defaults to
    null, meaning to use the default Web server. Using a virtual server here can speed up
    Web performance.

* `url_base` (string, default '/thumb/'): URL path, relative to the server, to find
    files.

* `ssl` (boolean, default null): Whether to use HTTPS for thumbnail URLs. Defaults to null,
    meaning to use other SSL settings.
    
* `dir` (string, default '/file/thumbnails/'): Path where to store thumbnails.

* `crop` (boolean, default false): Whether to crop thumbnails (or scale them down)

* `max_size_px` (integer, default 1000): Thumbnails with an edge greater than this will
    not be generated.
    
* `width` (integer, default 450): Width for generated thumbnails.

* `height` (integer, default 600): Heigth for generated thumbnails.

* `upscale` (boolean, default false): Whether to generate thumbnails bigger than the original.
  
* `animated` (boolean, default false): Whether to allow animated thumbnails.


theme
-------------------------------------------------------------------------------

* `server` (string, default null): Like avatars, you can speed up page loading
    by pointing the theme file lookup to another server (virtual or real).
    The default of null will use the same server as PA.

* `url_base` (string, default '/theme'): Path part of theme URLs, before the theme name.
    Relative to the theme server. It may make sense to change this path when upgrading,
    (using version numbers as the path) to make sure that all files are reloaded by
    caching clients or proxies.

* `ssl` (boolean, default null): Whether to use SSL for theme elements. Default
    is null, which means guess based on site SSL settings.

* `dir` (string, default "./themes"): Directory where theme files are stored.
    Used to determine whether to show parts of a theme file. Defaults to the
    theme subdirectory of the install directory.


plugins
-------------------------------------------------------------------------------

* `server` (string, default null): Server to find static files for a plugin when the page
	is plain old HTTP. Defaults to site/server (same as pages). You can use this to move
	plugin CSS and JS files to a CDN.

* `url_base` (string, default '/plugins/'): Path to the plugin files. Expects that each
    plugin will have a subdirectory at plugins/NameOfPlugin. Change this if you're using
     a CDN.

* `ssl` (boolean, default null) Whether to use ssl for files served by plugins.

* `core` (associative array, default TODO): Core GNU social modules, cannot be disabled.

* `default`: (associative array, default TODO): Mapping from plugin name to array of
    plugin arguments.

* `locale_path` (string, default null): Path for finding plugin locale files. In the
    plugin's directory by default.


license
-------------------------------------------------------------------------------

The default license to use for your users' notices. The default is the Creative Commons
Attribution 4.0 license, which is probably the right choice for any public site. Note that
some other servers will not accept notices if you apply a stricter license than this.

* `type` (enum["cc", "allrightsreserved", "private"], default 'cc'): One of 'cc' (for
    Creative Commons licenses), 'allrightsreserved' (default copyright), or 'private' (for
    private and confidential information).

* `owner` (string|boolean, default null): For 'allrightsreserved' or 'private', an
    assigned copyright holder (for example, an employer for a private site). Use true to
    attribute it to the poster.

* `url` (string, default 'https://creativecommons.org/licenses/by/4.0/'): URL of the
    license, used for links.

* `title` (string, default 'Creative Commons Attribution 4.0'): Title for the license.

* `image` (string, default '/theme/licenses/cc_by_4.0.png'): URL path for the license image.


mail
-------------------------------------------------------------------------------

This is for configuring out-going email. 

* `backend` (enum["mail", "sendmail", "smtp"], default 'mail'): The backend to use for
   mail. We recommend SMTP where your setup supports it as it is of the three the more
   difficult one for script exploits to abuse (relatively speaking - they all have
   potential problems.).

* `params` (array, default null): If the mail backend requires any parameters, you can
    provide them in this array.

* `domain_check` (boolean, default true): Check email origin is valid.


nickname
-------------------------------------------------------------------------------

* `blacklist` (array, default ['doc', 'main', 'avatar', 'theme']): an array of strings for
    usernames that may not be registered. You may want to add others if you have other
    software installed in a subdirectory of GNU social or if you just don't want certain
    words used as usernames.

* `featured` (array, default null): an array of nicknames of 'featured' users of the site.
    Can be useful to draw attention to well-known users, or interesting people, or
    whatever.


profile
-------------------------------------------------------------------------------

* `banned` (array, defualt []): array of users to hell-ban

* `bio_text_limit` (integer, default null): Max character length of bio; 0 means no
    limit; null means to use the site text limit default.

* `allow_nick_change` (boolean, default false): Whether to allow users to change their
    nickname.

* `allow_private_stream` (boolean, default true): Whether users can set their streams to
    private, so only followers can see it.

* `backup` (boolean, default false): Whether users can backup their own profiles. Can
    cause DoS.

* `restore` (boolean, default false): Whether users can restore their profiles from backup
    files. Can cause DoS.

* `delete` (boolean, default false): Whether users can delete their own accounts.

* `move` (boolean, default false): Whether users can move their accounts to another
    server.
  
  
image
-------------------------------------------------------------------------------

* `jpegquality` {integer, default 85}: default quality to use when reencoding images as
    jpeg.


theme_upload
-------------------------------------------------------------------------------

* `enabled` (boolean, default true): Whether to allow users to upload themes

* `formats` (array, default ['zip', 'tar', 'gz', 'tar.gz']): Formats to allow

foaf
-------------------------------------------------------------------------------

* `mbox_sha1sum` (boolean, default false): whether to include this box in the FOAF
    protocol page


public
-------------------------------------------------------------------------------

For configuring the public stream.

* `local_only` (boolean, default false): If set to true, only messages posted by users of
    this instance (rather than remote instances) are shown in the public stream.

* `blacklist` (array, default []): An array of IDs of users to hide from the public
    stream. Useful if you have someone making an excessive amount of posts to the site or
    some kind of automated poster, testing bots, etc.

* `exclude_sources` (array, default []): Sources of notices that should be kept off of
    the public timeline (because they're from automatic posters, for instance).


throttle
-------------------------------------------------------------------------------

For notice-posting throttles.

* `enabled` (boolean, default true): Whether to throttle posting.

* `count` (integer, default 20): Each user can make this many posts in 'timespan' seconds.
    So, if count is 100 and timespan is 3600, then there can be only 100 posts from a user
    every hour.

* `timespan` (integer, default 600): See 'count'.


invite
-------------------------------------------------------------------------------

* `enabled` (boolean, default true): Whether to allow users to send invites.


tag
-------------------------------------------------------------------------------

* `dropoff` (integer, default 86400 * 10): Exponential decay factor for tag listing, in
    seconds. You can twiddle with this to try to get better results for your site.

* `cutoff` (integer, default 86400 * 90): Cutoff, in seconds, before which to not look for
    notices.


popular
-------------------------------------------------------------------------------

* `dropoff` (integer, default 86400 * 10): Exponential decay factor for popular notices, in
    seconds. You can twiddle with this to try to get better results for your site.

* `cutoff` (integer, default 86400 * 90): Cutoff, in seconds, before which to not look for
    notices.


daemon
-------------------------------------------------------------------------------

* `piddir` (string, default `sys_get_temp_dir()`): Directory that daemon processes should
    write their PID file (process ID) to.

* `user` (string|integer, default false): If set, the daemons will try to change their
    effective user ID to this user before running. Probably a good idea, especially if you
    start the daemons as root.

* `group` (string|integer, default false): If set, the daemons will try to change their
    effective group ID to this named group.


ping
-------------------------------------------------------------------------------

Using the "XML-RPC Ping" method initiated by weblogs.com, the site can
notify third-party servers of updates.

* `notify` (array, default []): An array of URLs for ping endpoints.

* `timeout` (integer, default 2): Interval in seconds between notifications.


new_users
-------------------------------------------------------------------------------

* `default_subscriptions` (array, default null): Nickname of user accounts to
    automatically subscribe new users to. Typically this would be a system account for e.g.
    service updates or announcements. Users are able to unsub if they want.

* `welcome_user` (string, default null): Nickname of a user account that sends welcome
    messages to new users.

N.B. If either of these special user accounts are specified, the users should be created
     before the configuration is updated.


linkify
-------------------------------------------------------------------------------

* `bare_domain` (boolean, default false): Prepend schema to any linked domains (a href,
    not display text).
    
* `linkify_ipv4` (boolean, default false): Convert IPv4 addresses into hyperlinks.

* `linkify_ipv6` (boolean, default false): Convert IPv6 addresses into hyperlinks.


group
-------------------------------------------------------------------------------

* `max_aliases` (integer, default 3): Maximum number of aliases a group can have.
    Set to 0 or less to prevent aliases in a group.

* `description_limit` (integer, default null): Maximum number of characters to allow in
    group descriptions. null means to use the site-wide text limits. 0 means no limit.


people_tag
-------------------------------------------------------------------------------

* `max_tags` (integer, default 100): Maximum number of people tags a user can create.

* `max_people` (integer, default 500): Maximum number of people with the same user people tag.
  
* `allow_tagging` (associative array, default ['local' => true, 'remote' => true])>: Which
    kind of user to allow tagging.

* `description_limit` (integer, default null): Maximum tag description lenght.


search
-------------------------------------------------------------------------------

* `type` (enum('fulltext', 'like'), default 'like'): type of search. Ignored if PostgreSQL
    is enabled. Can either be 'fulltext' or 'like'. The former is faster and more
    efficient but requires the lame old MyISAM engine for MySQL. The latter will work with
    InnoDB but could be miserably slow on large systems.


html_filter
-------------------------------------------------------------------------------

* `tags` (array, default ['img', 'video', 'audio', 'script']): Remove tags from
    user/remotely generated HTML.


notice
-------------------------------------------------------------------------------

* `content_limit` (integer, default null): Max length of the plain-text content of a
    notice. Null means to use the site-wide text limit. 0 means no limit.

* `allow_private` (boolean, default false): Whether to allow users to post notices visible
    only to their subscribers.

* `hide_banned` (boolean, default true): Whether to hide hell-banned users' notices.


message
-------------------------------------------------------------------------------

* `content_limit` (integer, default null): Max length of the plain-text content of a
    message. Null means to use the site-wide text limit. 0 means no limit.


location
-------------------------------------------------------------------------------

* `share` (enum('always', 'user', 'never'), default 'user'): Whether to share user
    location. 'user' means each user can choose.
    

admin
-------------------------------------------------------------------------------

* `panels` (array, default ['site', 'user', 'paths', 'access', 'sessions', 'sitenotice',
  'license', 'plugins']): Which panels to include in the admin tab.
  

single_user
-------------------------------------------------------------------------------

If an installation has only one user, this can simplify a lot of the
interface. It also makes the user's profile the root URL.

* `enabled` (boolean, default value provided in configure): Whether to run in "single user mode".

* `nickname` (string, default null): nickname of the single user. If no nickname is
  specified, the site owner account will be used (if present).


robots_txt
-------------------------------------------------------------------------------

* `crawl_delay` (integer, default 0): if non-zero, this value is provided as the
    'Crawl-Delay:' for the robots.txt file. see
    <https://en.wikipedia.org/wiki/Robots_exclusion_standard#Crawl-delay_directive> for
    more information. Default is zero, no explicit delay.

* `disallow`(array, default ['main', 'settings', 'admin', 'search', 'message']): Array of
    paths to disallow. Ignored when site is private, in which case the entire site ('/')
    is disallowed.


nofollow
-------------------------------------------------------------------------------

We optionally put 'rel="nofollow"' on some links in some pages. The following
configuration settings let you fine-tune how or when things are nofollowed. See
http://en.wikipedia.org/wiki/Nofollow for more information on what 'nofollow' means.

* `subscribers` (boolean, default true): Whether to nofollow links to subscribers on the
    profile and personal pages.

* `members` (boolean, default true): Whether to nofollow links to members on the group
    page. Default true.

* `peopletag` (boolean, default true): Whether to nofollow links to people listed in the
    peopletag page. Default true.

* `external` (enum('always', 'sometimes', 'never'), default 'sometimes'): External links
    in notices. One of three values: 'always', 'sometimes', 'never'. If 'sometimes', then
    external links are not nofollowed on profile, notice, and favorites page. Default is
    'sometimes'.


url_shortener
-------------------------------------------------------------------------------

* `service` (string, default 'internal'): URL shortening service to use by default. Users
    can override individually.

* `max_url_length` (integer, default 100): If an URL is strictly longer than this limit,
    it will be shortened. Note that the URL shortener service may return an URL longer
    than this limit. Users can override. If set to 0, all URLs will be shortened.

* `max_notice_length` (integer, default null): If a notice is strictly longer than this
    limit, all URLs in the notice will be shortened. Users can override this.


http
-------------------------------------------------------------------------------

* `ssl_cafile` (string, default '/docker/certbot/files/live/'): location of the CA file
    for SSL connections. If not set, peers won't be able to verify our identity.

* `timeout` (integer, default `ini_get('default_socket_timeout')`): Timeout in seconds
    when to close a connection.

* `proxy_host` (string, default null): Host to use for proxying HTTP requests. If null,
	    doesn't use an HTTP proxy.

* `proxy_port` (integer, default null): Port to use to connect to HTTP proxy host.

* `proxy_user` (string, default null): Username to use for authenticating to the HTTP proxy.

* `proxy_password` (string, default null): Password to use for authenticating to the HTTP proxy.

* `proxy_auth_scheme` (TODO): Scheme to use for authenticating to the HTTP proxy.


discovery
-------------------------------------------------------------------------------

* `CORS` (boolean, default false): Whether to allow Cross-Origin Resource Sharing for
    service discovery (host-meta, XRD, etc.)


performance
-------------------------------------------------------------------------------

* `high` (boolean, default fakse): Disables some high-performance-intensity components.


login_command
-------------------------------------------------------------------------------

* `enabled` (boolean, default false): Whether to enable users to send the text 'login' to
    the site through any channel and receive a link to login to the site automatically in
    return. Possibly useful for users who primarily use an XMPP or SMS interface. Note
    that the security implications of this are pretty serious. You should enable it only
    after you've convinced yourself that it is safe.

