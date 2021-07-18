# TODO

Hi! Thank you for your interest in contributing, please refer to
[DOCUMENTATION/DEVELOPERS](https://notabug.org/diogo/gnu-social/src/nightly/DOCUMENTATION/DEVELOPERS)
to learn how you can contribute.

## Pending

### [Core]
- Improve Cronish
  - Run session garbage collection
  - Cleanup Email Registration
- [Security] Review confirmation codes
```
<includeals> my guess is that the author intent was that the admin wouldn't have to verify his account, but has misread the User::register docblock
<xmpp-gnu> [XRevan86] https://notabug.org/diogo/gnu-social/src/nightly/plugins/EmailRegistration/EmailRegistrationPlugin.php#L114
<xmpp-gnu> [XRevan86] https://notabug.org/diogo/gnu-social/src/nightly/classes/Confirm_address.php#L52-L68
<xmpp-gnu> [XRevan86] Here's why 13 symbols
<xmpp-gnu> [XRevan86] https://notabug.org/diogo/gnu-social/src/nightly/classes/User.php#L343-L349 I wonder why parts of the code reinvent the wheel.
<xmpp-gnu> [XRevan86] $result = Confirm_address::saveNew($user->id, $email, 'email');
<xmpp-gnu> [XRevan86] I think it can be replaced with that.
<xmpp-gnu> [XRevan86] https://notabug.org/diogo/gnu-social/src/nightly/actions/emailsettings.php#L357-L364
<xmpp-gnu> [XRevan86] https://notabug.org/diogo/gnu-social/src/nightly/actions/imsettings.php#L316-L325
<xmpp-gnu> [XRevan86] The last one is a bit different, as it's not email. But I think it still applies.
<xmpp-gnu> [XRevan86] diogo: Maybe saveNew can be extended to accept variable bitsize (or better charsize).
<xmpp-gnu> [XRevan86] https://notabug.org/diogo/gnu-social/src/nightly/actions/smssettings.php#L320-L328 then this also can be altered
<xmpp-gnu> [XRevan86] but with keeping the original 40 bitsize
```
- [Database] Move from Pear DB to [PDO_DataObject](https://github.com/roojs/PDO_DataObject)

There are more pending tasks/ideas to implement in this
[repo's issues](https://notabug.org/diogo/gnu-social/issues).

