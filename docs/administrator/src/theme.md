# Themes

As of right now, your ability change the theme is limited to CSS
stylesheets and some image files; you can't change the HTML output,
like adding or removing menu items, without the help of a plugin.

You can choose a theme using the $config['site']['theme'] element in
the config.php file. See below for details.

You can add your own theme by making a sub-directory of the 'theme'
subdirectory with the name of your theme. Each theme can have the
following files:

display.css: a CSS2 file for "default" styling for all browsers.
logo.png: a logo image for the site.
default-avatar-profile.png: a 96x96 pixel image to use as the avatar for
users who don't upload their own.
default-avatar-stream.png: Ditto, but 48x48. For streams of notices.
default-avatar-mini.png: Ditto ditto, but 24x24. For subscriptions
listing on profile pages.

You may want to start by copying the files from the default theme to
your own directory.
