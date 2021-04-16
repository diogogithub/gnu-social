# Docker Installation

## Installation with Docker

This installation method requires
[Docker](https://docs.docker.com/engine/install/) and [Docker
Compose](https://docs.docker.com/compose/install/). Use
`bin/configure` and pick `docker`, which enables all needed services
as containers, or `mixed` which lets you pick which services you'd
like to create containers for. This way you can use services in the
host machine, which may be useful if your host already has a
webserver, for instance.

If you elect to not use some service containers, check [Instal without
Docker with shell access](./install/no_docker_shell.md) for details on
the configuration of each service.

Please remember that for the installation `configure` script to use docker,
it is necessary that the executing user is in the docker group.

## Prerequisites

In order to host your GNU social instance, you'll need a domain:
 - DNS domain
 - `docker`
 - `docker-compose` 

If you don't have a fixed public IP, for local hosting or development,
or if you're behind a NAT, use a dynamic DNS solutions. Search for
`GnuDIP host` or `dynamic dns`. To use GnuDIP, [clone](https://notabug.org/someonewithpc/gnudip.git), then inspect and run
the `./install.sh` script. This allows you to have a domain that
dynamically points to your IP address.

If you want to install locally for development or experimenting purposes,
you can use `localhost` as the `root domain` while configuring the installation.
If you then specify a subdomain, don't forget to add it in the `/etc/hosts` file.

{{#include dns.md}}

{{#include tls.md}}

{{#include no_tls.md}}

## Configuration

{{#include bin-configure.md}}

## Permissions

The PHP docker container needs the GNU social folder to be owned by
the group 82 (www-data).

## Running

If you elected to use all or some containers, run `docker-compose up`
from the root of the project (the folder where the `.git` folder is).
In this form, the application can be stopped by pressing `C-c` (`^C`,
`CTRL + C`); pressing it again will force the containers to stop
immediately. However, this form will show you all logs, but in most
cases, you won't want to see those all the time. For that, run
`docker-compose up -d` from the same directory; The application can
then be stopped with `docker-compose down`.

