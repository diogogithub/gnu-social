#!/bin/sh

cat /var/nginx/social.conf | \
    sed -r "s/%hostname%/$domain/g; s/%hostname_root%/$domain_root/g" > \
        /etc/nginx/conf.d/social.conf
