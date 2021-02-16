The StoreRemoteMedia plugin downloads remotely attached files to local server.

IMPORTANT: If using both Embed and StoreRemoteMedia plugins, Embed should be added first.

Installation
============
add `addPlugin('StoreRemoteMedia');`
to the bottom of your config.php

Settings
========
domain_whitelist: Array of regular expressions. Always escape your dots and end your strings.
check_whitelist: Whether to check the domain_whitelist.

max_size: Max media size. Anything bigger than this is rejected. 10MiB by default.

When check_whitelist is set, only images from URLs matching a regex in the
domain_whitelist array are accepted for local storage.

Example
=======

```
addPlugin('StoreRemoteMedia', [
    'domain_whitelist' => [
        '^i\d*\.ytimg\.com$' => 'YouTube',
        '^i\d*\.vimeocdn\.com$' => 'Vimeo'
    ],
    'check_whitelist' => true,
]);
```
