# Installation

GNU social is intended to be easily installable in both a shared hosting environment or a private
host with shell access, or just with PHP execution.

If you need help, contact us on IRC on the `#social` room in freenode or XMPP at [xmpp:gnusocial@conference.bka.li](xmpp:gnusocial@conference.bka.li)

The recommended way of installing is to use [Docker](https://www.docker.com/), as this simplifies
configuration. GNU social is comprised of a variety of different services, such as a webserver, a
PHP execution environment, a database, etc. You may choose to use all, some, or none of these
services in Docker containers.

Pick one of the following installation methods:

 - [Install with Docker with shell access](./install/docker_shell.md)
 - [Install without Docker with shell access](./install/no_docker_shell.md)
 - [Install with Docker with web access](./install/docker_web.md) (requires access to PHP's `system()`, which may be disabled)
 - [Install without Docker with only web access](./install/no_docker_web.md)

Installation with Docker without shell access, such as in some shared hosting environments is
possible by configuring social locally and copying the files over, however this is not a supported
configuration.