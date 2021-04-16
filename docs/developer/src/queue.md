## Queues and daemons

Some activities that GNU social needs to do, like broadcasting with OStatus or
ActivityPub, SMS, XMPP messages and TwitterBridge operations, can be 'queued'
and done by off-line bots instead.

Run the queue handler with:

```sh
php bin/console messenger:consume async --limit=10 --memory-limit=128M --time-limit=3600
```

GNU social uses Symfony, therefore the [documentation on
queues](https://symfony.com/doc/current/messenger.html#deploying-to-production)
might be useful.

TODO queuing

#### OpportunisticQM plugin

This plugin is enabled by default. It tries its best to do background
jobs during regular HTTP requests, like API or HTML pages calls.

Since queueing system is enabled by default, notices to be broadcasted
will be stored, by default, into DB (table queue_item).

Whenever it has time, OpportunisticQM will try to handle some of them.

This is a good solution whether you:

* have no access to command line (shared hosting)
* do not want to deal with long-running PHP processes
* run a low traffic GNU social instance

In other case, you really should consider enabling the queuedaemon for
performance reasons. Background daemons are necessary anyway if you wish
to use the Instant Messaging features such as communicating via XMPP.

#### Queue deamon

It's recommended you use the deamon, you must be able to run
long-running offline processes, either on your main Web server or on
another server you control. (Your other server will still need all the
above prerequisites, with the exception of Apache.) Installing on a
separate server is probably a good idea for high-volume sites.

1. You'll need the "CLI" (command-line interface) version of PHP
   installed on whatever server you use.

   Modern PHP versions in some operating systems have disabled functions
   related to forking, which is required for daemons to operate. To make
   this work, make sure that your php-cli config (/etc/php5/cli/php.ini)
   does NOT have these functions listed under 'disable_functions':

       * pcntl_fork, pcntl_wait, pcntl_wifexited, pcntl_wexitstatus,
         pcntl_wifsignaled, pcntl_wtermsig

   Other recommended settings for optimal performance are:
       * mysqli.allow_persistent = On
       * mysqli.reconnect = On

2. If you're using a separate server for queues, install StatusNet
   somewhere on the server. You don't need to worry about the
   .htaccess file, but make sure that your config.php file is close
   to, or identical to, your Web server's version.

3. In your config.php files (on the server where you run the queue
    daemon), set the following variable:

       $config['queue']['daemon'] = true;

   You may also want to look at the 'Queues and Daemons' section in
   this file for more background processing options.

4. On the queues server, run the command scripts/startdaemons.sh.

This will run the queue handlers:

* queuedaemon.php - polls for queued items for inbox processing and
  pushing out to OStatus, SMS, XMPP, etc.
* imdaemon.php - if an IM plugin is enabled (like XMPP)
* other daemons, like TwitterBridge ones, that you may have enabled

These daemons will automatically restart in most cases of failure
including memory leaks (if a memory_limit is set), but may still die
or behave oddly if they lose connections to the XMPP or queue servers.

It may be a good idea to use a daemon-monitoring service, like 'monit',
to check their status and keep them running.

All the daemons write their process IDs (pids) to /var/run/ by
default. This can be useful for starting, stopping, and monitoring the
daemons. If you are running multiple sites on the same machine, it will
be necessary to avoid collisions of these PID files by setting a site-
specific directory in config.php:

       $config['daemon']['piddir'] = __DIR__ . '/../run/';

It is also possible to use a STOMP server instead of our kind of hacky
home-grown DB-based queue solution. This is strongly recommended for
best response time, especially when using XMPP.

