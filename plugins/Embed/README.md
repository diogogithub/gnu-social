The Embed plugin for using and representing both Open Graph and oEmbed data.

See: https://ogp.me/ and https://www.oembed.com/

Installation
============
This plugin is enabled by default.

Settings
========
* `domain_whitelist`: Array of regular expressions. Always escape your dots and end your strings.
* `check_whitelist`: Whether to check the domain_whitelist.
* `thumbnail_width`: Maximum width of the thumbnail in pixels. Defaults to global `[thumbnail][width]`.
* `thumbnail_height`: Maximum height of the thumbnail in pixels. Defaults to global `[thumbnail][height]`.
* `crop`: Crop to the thumbnail size and don't preserve the original file. Defaults to true.
* `max_size`: Max media size. Anything bigger than this is rejected. Defaults to global `[attachments][file_quota]`.

Relevant GNU social global settings
===================================

* `[attachments][show_html]`: Whether to show HTML oEmbed data.

Example
=======

```
$config['thumbnail']['width'] = 42;
$config['thumbnail']['height'] = 42;
$config['attachments']['show_html'] = true;
addPlugin('Embed', [
    'domain_whitelist' => [
        '^i\d*\.ytimg\.com$' => 'YouTube',
        '^i\d*\.vimeocdn\.com$' => 'Vimeo'
    ],
    'check_whitelist' => true
    ]
);
```