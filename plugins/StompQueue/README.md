StompQueuePlugin wraps the StompQueueManager class which is a queue manager
that uses STOMP as a communication method to some form of backing storage.

Installation
============

This plugin is replaces other queue manager plugins, such as UnQueue,
which enabled by default and which should, but is not required to be
disabled.

addPlugin('StompQueue', ['servers' => ['your-stomp-instance-and-port'],
                         'vhost' => 'your-vhost',
                         'username' => 'your-username',
                         'password' => 'your-password']);

Options
=======

servers (default: null) - array of server addresses to use
vhost (default: '') - configured vhost -- required
username (default: 'guest') -- configured username -- don't use the default
password (default: 'guest') -- configured password -- don't use the default
basename (default: "queue:gnusocial-{$site_name}") -- prefix for all queue names,
useful to avoid collisions. Cannot contain `/`
control (default: 'gnusocial:control') -- control channel name. Cannot contain `/`
breakout (default: null) -- array of queue names which should be broken out into a previously unused server
useTransactions (default: false) -- whether to use transactions, allowing rollbacks in case of failure
useAcks (default: false) -- whether to explicitly use acknowledgements when receiving a message.
Usefull to avoid timeouts and possibly reduce load on the STOMP server
manualFailover (default: false) -- whether to coordinate failover in PHP or to let all servers act
as one coordinated unit
defaultIdx (default: 0) -- index in the servers array which is used by default. Will be updated in case of an error
persistent (default: []) -- list of queues which should be persistent

Example
=======

In config.php

addPlugin('StompQueue', ['servers' => 'tcp://localhost:61613', 'vhost' => '/',
                         // Please don't actually use the default credentials
                         'username' => 'guest', 'password' => 'guest']);
