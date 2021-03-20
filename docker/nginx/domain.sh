#!/bin/sh

# Can't do sed inplace, because the file would be busy
cat /var/nginx/social.conf | \
    sed -r "s/%hostname%/${DOMAIN}/g;" > \
        /etc/nginx/conf.d/social.conf
