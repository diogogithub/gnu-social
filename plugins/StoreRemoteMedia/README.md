The StoreRemoteMedia plugin downloads remotely attached files to local server.

IMPORTANT: If using both Embed and StoreRemoteMedia plugins, Embed should be added first.

Installation
============
add `addPlugin('StoreRemoteMedia');`
to the bottom of your config.php

Settings
========
* `domain_whitelist`: Array of regular expressions. Always escape your dots and end your strings.
* `check_whitelist`: Whether to check the domain_whitelist.

When check_whitelist is set, only images from URLs matching a regex in the
domain_whitelist array are accepted for local storage.  

* `thumbnail_width`: Maximum width of the thumbnail in pixels. Defaults to global `[thumbnail][width]`.
* `thumbnail_height`: Maximum height of the thumbnail in pixels. Defaults to global `[thumbnail][height]`.
* `crop`: Crop to the thumbnail size and don't preserve the original file. Defaults to false.
* `max_size`: Max media size. Anything bigger than this is rejected. Defaults to global `[attachments][file_quota]`.

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
