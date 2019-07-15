#Simple way to Webfinger enable your domain -- needs PHP

##Step 1

Put the 'dot-well-known' on your website, so it loads at:

     https://another_cool.org/.well-known/

(Remember the . at the beginning of this one, which is common practice
for "hidden" files and why we have renamed it "dot-")

## Step 2

Edit the .well-known/webfinger/index.php file and replace "https://www.example.org/gnusocial/index.php" with the domain name
you're hosting the .well-known handler on.
