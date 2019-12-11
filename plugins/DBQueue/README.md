DBQueuePlugin wraps the DBQueueManager class which is a queue manager
that uses the database as it's backing storage.

Installation
============

This plugin is enabled by default and replaces other queue manager plugins, such as UnQueue.

addPlugin('DBQueue');

Example
=======

In config.php

addPlugin('DBQueue');
