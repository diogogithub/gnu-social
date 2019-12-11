RedisQueuePlugin wraps the RedisQueueManager class which is a queue manager
that uses Redis as it's backing storage.

Installation
============

This plugin replaces other queue manager plugins, such as UnQueue and DBQueue.
You don't have to disable them but it is recommended to only use a QueueManager
at a time.

addPlugin('RedisQueue', ['server' => 'your-redis-instance-and-port']);

Example
=======

In config.php

addPlugin('RedisQueue', ['server' => 'tcp://localhost:6379']);
