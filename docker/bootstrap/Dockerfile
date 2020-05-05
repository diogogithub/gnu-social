FROM nginx:alpine

RUN echo "Installing bootstrap utils"

RUN apk add curl certbot openssl > /dev/null

RUN echo '                                  \
server {                                    \
    listen [::]:80;                         \
    listen 80;                              \
    server_name %hostname%;                 \
    location /.well-known/acme-challenge/ { \
        root /var/www/certbot;              \
    }                                       \
}                                           \
' > /etc/nginx/conf.d/challenge.conf
