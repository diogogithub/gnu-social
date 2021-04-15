### Private

A GNU social node can be configured as "private", which means it will not
federate with other nodes in the network. It is not a recommended method
of using GNU social and we cannot at the current state of development
guarantee that there are no leaks (what a public network sees as features,
private sites will likely see as bugs).

Private nodes are however an easy way to easily setup collaboration and
image sharing within a workgroup or a smaller community where federation
is not a desired feature. Also, it is possible to change this setting and
instantly gain full federation features.

Access to file attachments can also be restricted to logged-in users only:

1. Add a directory outside the web root where your file uploads will be
   stored. Use this command as an initial guideline to create it:

       mkdir /var/www/gnusocial-files

2. Make the file uploads directory writeable by the web server. An
   insecure way to do this is (to do it properly, read up on UNIX file
   permissions and configure your webserver accordingly):

       chmod a+x /var/www/gnusocial-files

3. Tell GNU social to use this directory for file uploads. Add a line
   like this to your config.php:

       $config['attachments']['dir'] = '/var/www/gnusocial-files';
