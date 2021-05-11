The ExtendedProfile plugin adds additional profile fields such as:

* Phone
* IM
* Website
* Work experience
* Education

And allows administrators to define additional profile fields for the
users of a GNU social installation.

Installation
============
add

    addPlugin('ExtendedProfile');
    $config['admin']['panels'][] = 'profilefields';

to the bottom of your config.php

Note: This plugin is enabled by default on private instances.

Settings
========
none

