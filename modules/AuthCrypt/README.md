AuthCrypt allows for GNU social to use password_hash() hashing to store password credentials.

Settings
========
You can change these settings in `config.php` with `$config['AuthCryptModule'][{setting name}] = {new setting value};`.

Default values in parenthesis.

authoritative (false): Set this to true when _all_ passwords are hashed with password_hash()
    (warning: this may disable all other password verification, also when changing passwords!)
algorithm (PASSWORD_DEFAULT): A hashing algorithm to use, the default value is defined by PHP. See all supported strings at https://php.net/password-hash
algorithm_options (['cost' => 12] if "algorithm" is bcrypt): Hashing algorithm options. See all supported values at https://php.net/password-hash
statusnet (true): Do we check the password against legacy StatusNet md5 hash?
    (notice: will check password login against old-style hash and if 'overwrite' is enabled update using crypt())
overwrite (true): Do we overwrite password hashes on login if they used a different algorithm
    (notice: to make use of stronger security or migrate obsolete hashes, this must be true)
password_changeable (true): Enables or disables password changing.
    (notice: if combined with authoritative, it disables changing passwords and removes option from menu.)
autoregistration: This setting is ignored. Password can never be valid without existing User.
provider_name: This setting defaults to 'crypt' but is never stored anywhere.

Technical note: Many settings are inherited from the AuthenticationModule class.
