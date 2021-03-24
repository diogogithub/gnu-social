FROM alpine

RUN apk update && apk add postfix dovecot dovecot-pop3d opendkim s6

# Expose ports
EXPOSE 25 110 143 587 993 995

# Fixes problem with s6 (s6 claims it's a docker bug)
VOLUME ["/run"]

# Run start script
ENTRYPOINT /usr/bin/entrypoint.sh
