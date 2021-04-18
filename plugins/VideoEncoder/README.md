# FFmpeg plugin for GNU social
(c) 2020 Free Software Foundation, Inc

This is the README file for GNU social's ActivityPub plugin.
It includes general information about the plugin.

## About

This plugin adds FFmpeg support to GNU social via PHP-FFMpeg.

Currently it serves as a better performant and quality alternative to resize
animated GIFs than the ImageMagick plugin. However, it has the downside of
increasing a little the size of the original GIF images for some conversions.

## Settings

Make sure you've set the `upload_max_filesize` and `post_max_size` in php.ini
to be large enough to handle uploads if you ever experience some error with
fetching remote images.