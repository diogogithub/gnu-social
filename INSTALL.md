## GNU social

GNU social is a federated social network.

Installation can be done in multiple ways, but the simplest is using
`docker` and `docker-compose`. The compose file currently includes all
the necessary services for running the app. Running the database and
webserver outside of `docker` containers is currently not supported,
unless the app is installed without `docker`.

## With docker

### Requirements

In order to host your GNU social instance, you'll need a domain, a
server with a constant IP and `docker` and `docker-compose` installed
on your system.

Alternatively, for local hosting or development, behind a NAT, use a
dynamic DNS solutions. I recommend you go to
https://gnudip.datasystems24.net or another GnuDIP host and register.
Then clone https://notabug.org/someonewithpc/gnudip.git, inspect and
run the `./install.sh` script. This allows you to have a domain that
dynamically points to your IP address.

### TLS/SSL

Next, if you want to setup SSL (which you should in most cases,
exceptions being wanting to use the Thor network), you'll need a
certificate. There are multiple approaches to achieve this, among
which are using a proxy server capable of either proxying an HTTP
connection to HTTPS or an HTTPS connection to HTTPS, or creating a
certificate signed by Let's Encrypt. For the former, follow the
instructions of your proxy provider.

If you're not using a proxy, you can use the
`bin/bootstrap_certificates` script to generate and install
certificates signed by Let's Encrypt. To do this, you should add the
server's IP (even if it's dynamic) as an `A` DNS record with your DNS
provider (normally, your domain registrar). The `A` record doesn't
need to be at the root of your domain, meaning it's name can be a
subdomain. Then, run the aforementioned script and fill in the
details; this requires `docker` and `docker-compose` and will create
the requested certificate.

After doing the above, if you don't have a static IP, go to your DNS
manager, delete the `A` record you created in the previous step and
create a `CNAME` record pointing from the domain you want to use the
domain you registered with the GnuDIP host.

### No TLS/SSL

Edit the `docker-compose.yaml` file and comment the `certbot` service
to disable it. In the future, this will be handled by the
`bin/configure` script.

### Configuration

Run the `bin/configure` script and enter the information as asked.
This will generate all the required `.env` files used by
`docker-compose` to configure the application.

### Installation/Running

Simply run `docker-compose up` from the root of the project (the
folder where the `.git` folder is). In this form, the application can
be stopped by pressing `C-c` (`CTRL` + `C`); pressing it again will
force the containers to stop immediately. However, this form will show
you all logs, but in most cases, you won't want to see those all the
time. For that, run `docker-compose up -d` from the same directory;
The application can then be stopped with `docker-compose down`.

## Without docker

Coming soon (TM)
