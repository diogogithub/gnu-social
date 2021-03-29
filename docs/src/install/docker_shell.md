# Docker Installation

## Installation with Docker

This installation method required
[Docker](https://docs.docker.com/engine/install/) and [Docker
Compose](https://docs.docker.com/compose/install/). Use
`bin/configure` and pick `docker`, which enables all needed services
as containers, or `mixed` which let's you pick which services you'd
like to create containers for. This way you can use services in the
host machine, which may be useful if your host already has a
webserver, for instance.

## Prerequisites

In order to host your GNU social instance, you'll need a domain:
 - DNS domain
 - `docker`
 - `docker-compose` 

If you don't have a fixed public IP, for local hosting or development,
or if you're behind a NAT, use a dynamic DNS solutions. Search for
`GnuDIP host` or `dynamic dns`. To use GnuDIP, clone
[](https://notabug.org/someonewithpc/gnudip.git), then inspect and run
the `./install.sh` script. This allows you to have a domain that
dynamically points to your IP address.

## Configuring TLS/SSL

You should configure a valid certificate and use TLS/SSL in most cases,
one exception being wanting to use the Tor network.

The `bin/configure` script is capable of setting this up for you, with
the help of EFF's `certbot` and Let's Encrypt.

There are multiple approaches to achieve this, among which are using
your own (non-self) signed certificate, or using a proxy service
capable of either proxying an HTTP connection to HTTPS (not
recommended) or an HTTPS connection to HTTPS. For this approach,
follow the instructions of your proxy service provider, but generally
you'll use a self signed certificate, which the configuration script
can generate.

TODO Mail server configuration (links below)

GNU social can be configured to send emails for various reasons. See
[mail server configuration](). You'll need a certificate for your web
domain and your mail domain, which may or may not be the same (if you
use the same hostname for both, or a certificate valid for both).

If you prefer to not use Let's Encrypt, pick `mixed` and uncheck the
`certbot` service. Place your certificate in the folder
`docker/certbot/.files/live/$HOSTNAME/`, where `$HOSTNAME` is the name
where you want to host your node, such as `social.yourdomain`.
Remember you also need a certificate for your mail server.

TODO improve external certificate handling

### Configuring DNS

In order for your GNU social node to be accessible with your chosen
hostname, you can create an `A` or `AAAA` DNS record, with your
server's fixed IP v4 or v6 respectively in your DNS provider
(normally, your domain registrar); the `A` record doesn't need to be
at the root of your domain, meaning it's name can be a subdomain. For
dynamic IPs, create a `CNAME` record pointing to the hostname you
created with your chosen Dynamic DNS host. A `CNAME` cannot normally be created
for a domain root, so you must use a subdomain. Note that some DNS
providers provide 'CNAME flattening', in which case you can use your
root domain.

After this, run the `bin/configure` script (not as root).


## Without TLS/SSL

This is not recommended unless you know what you're doing. One
exception is if you want your node to be used with the Tor network.

Pick 'mixed' and uncheck the `certbot` service
to disable it.


## Configuration

TODO more detail

Run the `bin/configure` script and enter the information as asked.
This will generate all the required `.env` files and (optionally) a
`docker-compose.yaml` file.

## Running

If you elected to use all or some containers, run `docker-compose up`
from the root of the project (the folder where the `.git` folder is).
In this form, the application can be stopped by pressing `C-c` (`^C`,
`CTRL + C`); pressing it again will force the containers to stop
immediately. However, this form will show you all logs, but in most
cases, you won't want to see those all the time. For that, run
`docker-compose up -d` from the same directory; The application can
then be stopped with `docker-compose down`.

