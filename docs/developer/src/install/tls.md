## Configuring TLS/SSL

You should configure a valid certificate and use TLS/SSL in most cases,
one exception being wanting to use the Tor network.

The `bin/configure` script is capable of setting this up for you if you use a
Docker container. Otherwise, using [certbot](https://certbot.eff.org/) and
[Let's Encrypt](https://letsencrypt.org/) is recommended

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

TODO improve external certificate handling

If you prefer to not use Let's Encrypt, or the docker container, pick
`mixed` and uncheck the `certbot` service or pick `external`.

Place your certificate in the folder
`docker/certbot/.files/live/$HOSTNAME/`, where `$HOSTNAME` is the name
where you want to host your node, such as `social.yourdomain`.
Remember you also need a certificate for your mail server.
