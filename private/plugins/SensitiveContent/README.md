# "Sensitive" Content Plugin for GNU social

## About

Adds a setting to allow a user to hide #NSFW-hashtagged notices behind a
blocker image until clicked.

Works for both vanilla GNU social and with the Qvitter plugin.

## Settings

If you want to customize the blocker image, add a line to your config.php:

    addPlugin('SensitiveContent', ['blockerimage' => '/path/to/image.jpg']);

if you want to activate the nsfw overlay for non-logged-in visitors add:

    addPlugin('SensitiveContent', ['hideforvisitors' => true]);

## Usage

Individual users must go to their Settings page. A new sidebar menu item "Sensitive Content"
will be available. User checks or unchecks the checkbox on this page, and presses save.


If you have GNU Social open in other browser tabs, refresh them. If you are using Qvitter, also
refresh, but because Qvitter caches notices on the client side, only new sensitive images will
be hidden, it will not apply to notices retroactively unless you clear your browser cache.

## License

GNU AGPL v3 or later

## Thanks

Thanks in particular to Hannes and Qvitter because looking at his code helped me a lot.

A tiny bit of content was taken from Qvitter to enhance Qvitter with this functionality.

