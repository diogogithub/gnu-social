# No Docker and shell installation

## Prerequisites

The following software packages are *required* for this software to
run correctly.

 - PHP 8.0+
 - Postgres 10+/MariaDB 10.2+
 - Web server
 - Mail server

Apache, lighttpd and nginx will all work. CGI mode is recommended and
also some variant of 'suexec' (or a properly setup php-fpm pool)
NOTE: mod_rewrite or its equivalent is extremely useful.

The mail server is used for sending notifications and password resets,
among other things.

### PHP modules

Your PHP installation must include the following PHP extensions for a
functional setup of GNU social:

 - bcmath     Arbitrary Precision Mathematics
 - ctype      Locale support
 - curl       Fetching files by HTTP.
 - exif       Exchangeable image information.
 - gd         Image manipulation (scaling).
 - gmp        For Salmon signatures (part of OStatus)
 - iconv      Locale support
 - intl       Internationalization support (transliteration et al).
 - json       For WebFinger lookups and more.
 - mbstring   String manipulation
 - mysql      The native driver for MariaDB connections.
 - opcache    Improved PHP performance by precompilation
 - openssl    (compiled in for Debian, enabled manually in Arch Linux)
 - pcre       Perl Compatible Regular Expression
 - readline   For interactive scripts
 - Session    User sessions
 - SimpleXML  XML parser
 - Tokenizer  Reflection and annotations

NOTE: Some distros require manual enabling in the relevant php.ini for
some modules, even if they're included in the main PHP package.

#### Better performance

For some functionality, you will also need the following extensions:

 - opcache       Improves performance a _lot_. Included in PHP, must be
                 enabled manually in php.ini for most distributions. Find
                 and set at least:  opcache.enable=1
 - mailparse     Efficient parsing of email requires this extension.
                 Submission by email or SMS-over-email uses this.
 - sphinx        A client for the sphinx server, an alternative to MySQL
                 or Postgresql fulltext search. You will also need a
                 Sphinx server to serve the search queries.
 - gettext       For multiple languages. Default on many PHP installs;
                 will be emulated if not present.
 - exif          For thumbnails to be properly oriented.

You may also experience better performance from your site if you configure
a PHP cache/accelerator. Most distributions come with "opcache" support.
Enable it in your php.ini where it is documented together with its settings.

{{#include dns.md}}

{{#include tls.md}}

{{#include no_tls.md}}

### Getting it up and running

Installing the basic GNU Social web component is relatively easy,
especially if you've previously installed PHP packages.

 1. Download and unpack the release tarball or clone the `git` repository on
    your Web server. Usually a command like this will work:

    ```
    tar zxf gnusocial-*.tar.gz
    ```

   ...which will make a `gnusocial-x.y.z` directory in your current directory.
   (If you don't have shell access on your Web server, you may have to unpack
   the tarball on your local computer and FTP the files to the server. Checkout
   [Instal without Docker with only web access](./install/no_docker_web.md))

 2. Move the tarball to a directory of your choosing in your Web root
    directory. Usually something like this will work:

    ```
    mv gnusocial-x.y.z /var/www/gnusocial
    ```

    This will often make your GNU social instance available in the gnusocial
    path of your server, like "http://example.net/gnusocial". "social" or
    "blog" might also be good path names. If you know how to configure
    virtual hosts on your web server, you can try setting up
    "http://social.example.net/" or the like.
 
    You need "rewrite" support on your webserver. This is used for "Fancy URL"
    support, which you can read more about further down in this
    document.
 
 3. Make your target directory writeable by the Web server, please note however
    that 'a+w' will give _all_ users write access and securing the webserver is
    not within the scope of this document, but reading more on this subject is
    recommended.

    ```
    chmod a+w /var/www/gnusocial/
    ```
 
    On some systems, this will work as a more secure alternative:

    ```
    chgrp www-data /var/www/gnusocial/
    chmod g+w /var/www/gnusocial/
    ```
 
    If your Web server runs as another user besides "www-data", try
    that user's default group instead. As a last resort, you can create
    a new group like "gnusocial" and add the Web server's user to the group.
 
 4. Create a database to hold your site data. Something like this
    should work (you will be prompted for your database password):

    ```
    mysqladmin -u "root" -p create social
    ```
 
    Note that GNU social should have its own database; you should not share
    the database with another program. You can name it whatever you want,
    though.
  
    (If you don't have shell access to your server, you may need to use
    a tool like phpMyAdmin to create a database. Check your hosting
    service's documentation for how to create a new database.)
  
 5. Create a new database account that GNU social will use to access the
    database. If you have shell access, this will probably work from the
    MariaDB/PostgreSQL shell:
  
        GRANT ALL on social.*
        TO 'social'@'localhost'
        IDENTIFIED BY 'agoodpassword';
  
    You should change the user identifier 'social' and 'agoodpassword'
    to your preferred new database username and password. You may want to
    test logging in to MariaDB/PostgreSQL as this new user.
  
 6. Run `bin/configure`

{{#include bin-configure.md}}
  
 7. You should now be able to navigate to your social site's main directory
    and see the "Public Feed", which will probably be empty. You can
    now register new user, post some notices, edit your profile, etc.

### Fancy URLs

By default, GNU social will use URLs that include the main PHP program's
name in them. For example, a user's home profile might be found at either
of these URLS depending on the webserver's configuration and capabilities:

    https://social.example.net/index.php/fred
    https://social.example.net/index.php?p=fred

It's possible to configure the software to use fancy URLs so it looks like
this instead:

    https://social.example.net/fred

These "fancy URLs" are more readable and memorable for users. To use
fancy URLs, you must either have Apache 2.x with .htaccess enabled and
mod_rewrite enabled, -OR- know how to configure "url redirection" in
your server (like lighttpd or nginx).

TODO Add webserver sample configs

1. See the instructions for each respective webserver software

 - For Apache, inspect the `docs/webserver/htaccess.sample` file and save it as
   `.htaccess` after making any necessary modifications. Our sample
   file is well commented.
 - For lighttpd, inspect the `docs/webserver/lighttpd.conf.example` file and apply the
   appropriate changes in your virtualhost configuration for lighttpd.
 - For nginx, inspect the `docs/webserver/nginx.conf.sample` file and apply the appropriate
   changes.
 - For other webservers, we gladly accept contributions of
   server configuration examples.

2. Ensure your webserver is properly configured and has its settings
applied (remember to reload/restart it)

