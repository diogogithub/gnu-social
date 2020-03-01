
Make sure to set the permissions of this folder to group and group 82, as that's what php-fpm uses

# groupadd -g 82 www-data
# useradd -u 82 -g 82 -r -s /usr/bin/nologin www-data
# chown $USER:www-data social file public public/install.php public/index.php
# chmod -R g=wrx social file public public/install.php public/index.php