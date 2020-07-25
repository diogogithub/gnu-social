AuthCrypt allows for GNU social to use password_hash() hashing to store password credentials.

Settings
========
You can change these settings in `config.php` with `$config['AuthCryptModule'][{setting name}] = {new setting value};`.

Default values in parenthesis.

authoritative (false): Set this to true when _all_ passwords are hashed with crypt()
    (warning: this may disable all other password verification, also when changing passwords!)
statusnet (true): Do we check the password against legacy StatusNet md5 hash?
    (notice: will check password login against old-style hash and if 'overwrite' is enabled update using crypt())
overwrite (true): Do we overwrite old style password hashes with crypt() hashes on password change?
    (notice: to make use of stronger security or migrate to crypt() hashes, this must be true)
password_changeable (true): Enables or disables password changing.
    (notice: if combined with authoritative, it disables changing passwords and removes option from menu.)
autoregistration: This setting is ignored. Password can never be valid without existing User.
provider_name: This setting defaults to 'crypt' but is never stored anywhere.

Technical note: Many settings are inherited from the AuthenticationModule class.
