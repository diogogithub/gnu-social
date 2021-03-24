#!/bin/sh

# Can't do sed inplace, because the file would be busy
cat /var/nginx/social.conf | \
    sed -r "s/%hostname%/${WEB_DOMAIN}/g;" > \
        /etc/nginx/conf.d/social.conf
