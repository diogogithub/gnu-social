Most of this directory contents are patched PEAR libraries (necessary as PEAR packages are no longer maintained)

List of external libraries
--------------------------

A number of external PHP libraries are used to provide basic
functionality and optional functionality for your system. For your
convenience, they are available in the "extlib" directory of this
package, and you do not have to download and install them. However,
you may want to keep them up-to-date with the latest upstream version,
and the URLs are listed here for your convenience.

- DB_DataObject http://pear.php.net/package/DB_DataObject
- Validate http://pear.php.net/package/Validate
- PEAR Mail, for sending out mail notifications
  http://pear.php.net/package/Mail
- PEAR Net_SMTP, if you use the SMTP factory for notifications
  http://pear.php.net/package/Net_SMTP
- PEAR Net_Socket, if you use the SMTP factory for notifications
  http://pear.php.net/package/Net_Socket
- OAuth.php from http://oauth.googlecode.com/svn/code/php/
(has been edited to avoid colliding autoload)


- PEAR Validate is used for URL and email validation.
- Console_GetOpt for parsing command-line options.
- HTTP_Request2, a library for making HTTP requests.
- PEAR Net_URL2 is an HTTP_Request2 dependency.

TODO
----

- Port from PEAR NET to Guzzle
- Port from PEAR DB to Doctrine DBAL
- Port from PEAR mail to PHPMailer
- eventually port OAuth to something more modern

Why not replace all the components with newer ones? We don't think the alternatives really meet our needs or are at
all necessary and/or better solutions. The code of these patched libraries that we are maintaing is quite good.
