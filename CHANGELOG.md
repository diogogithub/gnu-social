# GNU social - Log of Changes

## 2.0.0 - THIS. IS. GNU SOCIAL!!! [WIP]

Release name chosen after 300 by Frank Miller where the main protagonist Leonidas, King of Sparta, declines peace with the
Persians, after being disrespected, by shouting at the Persian Messenger "This is Sparta!" and kicking him into a large well
proceeded by the killing of the other Persian messengers.

### Major changes from previous release:

Load and Storage:
- New media handling system
- GS is now structurely divided in includes and public
- OEmbed upgraded to Embed plugin (Now we provide Open Graph information too)
- Added Redis based caching and queues
- Improved memcached support

Federation:
- Added ActivityPub support
- RemoteFollow: Remote follow ActivityPub and OStatus remote actors via the click of a button
- ActorLists: Allow to create collections of Actors and to interact with them - supports both OStatus and ActivityPub
- The Free Network: Automagically migrate internal remote profiles between Free Network protocols
- Enabled the search box to import remote notices and profiles
- Improvements on Nodeinfo (and new route)

General:
- Composer was integrated

Modules:
- Restored built-in plugins
- New modules system: core plugins and plugins physically separated
- Bug fixes of core modules logic
- Improved plugin management tool (now all available plugins are shown and third party can be uploaded and deleted)

## v1.20.9release - The Invicta Crusade

Dropped Support for PHP5.6.x. Minimum PHP version now is 7.0.0.

Release name chosen after Porto city. Porto is one of the oldest cities in Europe and thanks to its fierce resistance during
two battles and sieges in history, it has earned the epithet of ‘Cidade Invicta’ (Invincible City). The dev team behind this
release studies in Porto, Portugal.

Major changes from previous release:

- Various patches on PEAR related components
- Various database related improvements
- Improved XMPP support
- Added Nodeinfo support
- Various i18n and l10n bug fixes
- Improvements on Internal Session Handler
- Improvements on OpenID support
- Improved Media handling and safer upload
- Redirect to previous page after login
- Initial work on full conversion to PHP7
- Initial work on a better documentation
- Allow login with email
- Various bug fixes

## v1.2.0beta4 - The good reign of PHP5

Dropped support for PHP5.4.

New this version

This is the development branch for the 1.2.x version of GNU social. All daring 1.1.x admins should upgrade to this version.

So far it includes the following changes:

- Backing up a user's account is more and more complete.
- Emojis 😸 (utf8mb4 support)

The last release, 1.1.3, gave us these improvements:

- XSS security fix (thanks Simon Waters, https://www.surevine.com/)
- Many improvements to ease adoption of the Qvitter front-end https://github.com/hannesmannerheim/qvitter
- Protocol adaptions for improved performance and stability

Upgrades from StatusNet 1.1.1 will also experience these improvements:

- Fixes for SQL injection errors in profile lists.
- Improved ActivityStreams JSON representation of activities and objects.
- Upgrade to the Twitter 1.1 API.
- More robust handling of errors in distribution.
- Fix error in OStatus subscription for remote groups.
- Fix error in XMPP distribution.
- Tracking of conversation URI metadata (more coherent convos)

## v1.1.3release - The Spanish Invasion

New this version

This is a security fix and bug fix release since 1.1.3-beta2. All 1.1.x sites should upgrade to this version.

So far it includes the following changes:

- XSS security fix (thanks Simon Waters, https://www.surevine.com/)
- Many improvements to ease adoption of the Qvitter front-end https://github.com/hannesmannerheim/qvitter
- Protocol adaptions for improved performance and stability
- Backing up a user's account now appears to work as it should

Upgrades from StatusNet 1.1.1 will also experience these improvements:

- Fixes for SQL injection errors in profile lists.
- Improved ActivityStreams JSON representation of activities and objects.
- Upgrade to the Twitter 1.1 API.
- More robust handling of errors in distribution.
- Fix error in OStatus subscription for remote groups.
- Fix error in XMPP distribution.
- Tracking of conversation URI metadata (more coherent convos)
