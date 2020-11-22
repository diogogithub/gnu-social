FROM debian:buster-slim

ENV DEBIAN_FRONTEND=noninteractive 

# Install packages
RUN \
	apt-get update \
	&& apt-get upgrade -y \
	&& apt-get install -y --no-install-recommends \
		dovecot-core \
		dovecot-imapd \
		dovecot-lmtpd \
		dovecot-pop3d \
		opendkim \
		opendkim-tools \
		openssl \
		postfix \
		procps \
		rsyslog \
		s6 \
	&& apt-get autoclean \
	&& apt-get autoremove

# Setup folders and users
RUN \
	groupadd -g 2222 vmail \
	&& useradd -d /var/mail -M -s /usr/sbin/nologin -u 2222 -g 2222 vmail \
	&& usermod -aG vmail postfix \
	&& usermod -aG vmail dovecot \
	&& usermod -aG vmail opendkim\
	&& mkdir -p -m 751 /var/mail/ \
	&& mkdir -p -m 755 /etc/mail/ \
	&& chown vmail:vmail /var/mail
	
# Copy config files
COPY rootfs/ /

RUN \
	touch /etc/mail/aliases /etc/mail/domains /etc/mail/mailboxes /etc/mail/passwd \
	&& postmap /etc/mail/aliases && postmap /etc/mail/domains && postmap /etc/mail/mailboxes

RUN \
	chmod +x /etc/service/postfix/run \
	&& chmod +x /etc/service/dovecot/run \
	&& chmod +x /etc/service/opendkim/run \
	&& chmod +x /etc/service/rsyslog/run

# Expose ports
EXPOSE 25 110 143 587 993 995

# Run start script
ENTRYPOINT /usr/bin/start.sh
