FROM debian:buster-slim

ENV \
	DEBIAN_FRONTEND=noninteractive \
	DOMAINNAME=example.com \
	MAILNAME=mail \
	POSTMASTER=postmaster@example.com \
	SSL_CERT=/etc/ssl/mailserver.crt \
	SSL_KEY=/etc/ssl/mailserver.key
	
RUN \
	apt-get update \
	&& apt-get upgrade -y \
	&& apt-get install -y --no-install-recommends \
		postfix \
		telnet \
		dovecot-core \
		dovecot-pop3d \
		dovecot-imapd \
		dovecot-lmtpd \
		supervisor \
		rsyslog \
		opendkim \
		opendkim-tools \
	&& apt-get autoclean \
	&& apt-get autoremove

RUN \
	rm /etc/postfix/main.cf /etc/postfix/master.cf /etc/dovecot/dovecot.conf /etc/dovecot/conf.d/* /etc/rsyslog.conf /etc/rsyslog.d/* \
	&& groupadd -g 5000 vmail \
	&& useradd -d /var/mail -M -s /usr/sbin/nologin -u 5000 -g 5000 vmail \
	&& usermod -aG vmail postfix \
	&& usermod -aG vmail dovecot \
	&& usermod -aG vmail opendkim\
	&& mkdir -p -m 751 /var/mail/ \
	&& mkdir -p -m 755 /etc/mail/ \
	&& mkdir -p -m 755 /etc/ssl/ \
	&& mkdir -p -m 700 /etc/opendkim/keys \
	&& chown vmail:vmail /var/mail \
	&& chown opendkim:opendkim /etc/opendkim/keys
	
# Copy config files
COPY rootfs/ /

EXPOSE 25 143 587 993

ENTRYPOINT /usr/bin/start.sh
