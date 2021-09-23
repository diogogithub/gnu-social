#!/bin/sh

# There's no point in keeping default nginx conf
# Furthermore, this is an issue when a developer is using localhost as the instance domain
rm -f /etc/nginx/conf.d/default.conf

# Can't do sed inplace, because the file would be busy
cat /var/nginx/social.conf | \
    sed -r "s/%hostname%/${WEB_DOMAIN}/g;" > \
        /etc/nginx/conf.d/social.conf
