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
