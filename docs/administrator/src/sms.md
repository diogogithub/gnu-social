### SMS

StatusNet supports a cheap-and-dirty system for sending update messages
to mobile phones and for receiving updates from the mobile. Instead of
sending through the SMS network itself, which is costly and requires
buy-in from the wireless carriers, it simply piggybacks on the email
gateways that many carriers provide to their customers. So, SMS
configuration is essentially email configuration.

Each user sends to a made-up email address, which they keep a secret.
Incoming email that is "From" the user's SMS email address, and "To"
the users' secret email address on the site's domain, will be
converted to a notice and stored in the DB.

For this to work, there *must* be a domain or sub-domain for which all
(or most) incoming email can pass through the incoming mail filter.

1. Run the SQL script carrier.sql in your StatusNet database. This will
   usually work:

       mysql -u "statusnetuser" --password="statusnetpassword" statusnet < db/carrier.sql

   This will populate your database with a list of wireless carriers
   that support email SMS gateways.

2. Make sure the maildaemon.php file is executable:

       chmod +x scripts/maildaemon.php

   Note that "daemon" is kind of a misnomer here; the script is more
   of a filter than a daemon.

2. Edit /etc/aliases on your mail server and add the following line:

       *: /path/to/statusnet/scripts/maildaemon.php

3. Run whatever code you need to to update your aliases database. For
   many mail servers (Postfix, Exim, Sendmail), this should work:

       newaliases

   You may need to restart your mail server for the new database to
   take effect.

4. Set the following in your config.php file:

       $config['mail']['domain'] = 'yourdomain.example.net';
