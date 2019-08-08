GNU social Profile Extensions
=============================

Allows administrators to define additional profile fields for the
users of a GNU social installation.


Installation
------------

To enable, add the following lines to your config.php file:

addPlugin('GNUsocialProfileExtensions');
$config['admin']['panels'][] = 'profilefields';

