# GNU social v2.0.0 - THIS. IS. GNU SOCIAL!!!

Release name chosen after 300 by Frank Miller where the main protagonist Leonidas, King of Sparta, declines peace with the
Persians, after being disrespected, by shouting at the Persian Messenger "This is Sparta!" and kicking him into a large well
proceeded by the killing of the other Persian messengers.

## For users/sysadmins

### Web server changes
- GS is now structurely divided in includes and public
- New media handling system, new important settings, refer to CONFIGURE doc and web server conf
  - `$config['site']['x-static-delivery']`
  - You must also review the [attachments section of CONFIGURE](https://notabug.org/diogo/gnu-social/src/new_modules_system/DOCUMENTATION/SYSTEM_ADMINISTRATORS/CONFIGURE.md#attachments).
- OEmbed upgraded to Embed plugin (and now we provide Open Graph information too)
- Composer was integrated

### Functionality
- Restored broken built-in plugins
- A more powerful Plugins management tool for sysadmins

### Federation
- Added ActivityPub support
- Enabled the search box to import remote notices and profiles
- Direct messages

### Load and Storage
- Improved Cronish
  - Run session garbage collection
  - Cleanup Email Registration
- New queues system
- Support for Redis was added
- Support for PostgreSQL was added

## For developers

### APIs
- New Internal Session Handler and consequently a new API, migration should be simple enough
- Dropped support for StatusNet plugins, devs should now use GNUsocial class instead

### Modules
- Composer was integrated
- GS is now structurely divided in includes and public
- A more powerful Plugins management tool for sysadmins

Now plugins can be installed via the sysadmin UI. Therefore, now you should package your plugins either in tar or zip. The
package name must be the same as the plugin's internal name. For example, a plugin
named `Chuck Norris (social@chuck.norris)` must be in a file named: `ChuckNorris.tar`.
Inside there MUST be two directories, one named 'includes' with everything that should be unpacked
in `local/plugins/{plugin_name}` and another named 'public' with everything that should be unpacked
in `public/local/plugins/{plugin_name}`.
