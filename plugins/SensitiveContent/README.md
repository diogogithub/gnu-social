# "Sensitive" Content Plugin for GNU Social

## About

WARNING: THIS IS ALPHA CODE, IT IS PRERELEASE AND SHOULD ONLY BE INSTALLED TO
HELP TEST OR YOU ARE WILLING TO TAKE RISKS.

Create user option to allow a user to hide #NSFW-hashtagged notices behind a
blocker image until clicked.

Works for both vanilla GNUSocial and with the Qvitter plugin.

## Install

- Move the project directory to ${GNU_SOCIAL}/plugins
- Add addPlugin('SensitiveContent'); to your config.php

if you want to customize the blocker image, add a line to your config.php:

  $config['site']['sensitivecontent']['blockerimage'] = "/path/to/image.jpg";

## Usage

Individual users must go to their Settings page. A new sidebar menu item "Sensitive Content"
will be available. User checks or unchecks the checkbox on this page, and presses save.


If you have GNU Social open in other browser tabs, refresh them. If you are using Qvitter, also
refresh, but because Qvitter caches notices on the client side, only new sensitive images will
be hidden, it will not apply to notices retroactively unless you clear your browser cache.

## License

GNU Affero License

## Thanks

Thanks in particular to Hannes and Qvitter because looking at his code helped me a lot.

A tiny bit of content was taken from Qvitter to enhance Qvitter with this functionality.

