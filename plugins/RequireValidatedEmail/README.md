This plugin disables posting for accounts that do not have a
validated email address.

Example:
```
  addPlugin('RequireValidatedEmail');
```

If you don't want to apply the validation equirement to existing accounts, you
can specify a date users registered before which are exempted from validation.
```
    addPlugin('RequireValidatedEmail', [
        'exemptBefore' => '2009-12-07',
    ]);
```

You can also exclude the validation checks from OpenID accounts
connected to a trusted provider, by providing a list of regular
expressions to match their provider URLs.

For example, to trust WikiHow and Wikipedia users:
```
    addPlugin('RequireValidatedEmailPlugin', [
        'trustedOpenIDs' => [
            '!^https?://\w+\.wikihow\.com/!',
            '!^https?://\w+\.wikipedia\.org/!',
        ],
    ]);
```

Todo:
  * add a more visible indicator that validation is still outstanding
  * test with XMPP, API posting
