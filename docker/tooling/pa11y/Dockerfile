FROM node

RUN apt-get update && apt-get -y install npm openssh-server && npm install -g pa11y

COPY sshd_config /etc/ssh/sshd_config
RUN echo 'root:pa11y' | chpasswd

ENTRYPOINT service ssh start && sleep infinity
