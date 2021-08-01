Logging
=======

GNU social comes with a minimalist logger class.
In conformance with [the twelve-factor app methodology](https://12factor.net/logs),
it sends messages starting from the `WARNING` level to `stderr`.

The minimal log level can be changed by setting the `SHELL_VERBOSITY` environment variable:


`SHELL_VERBOSITY` value | Minimum log level
------------------------|------------------
`-1`                    | `ERROR`
`1`                     | `NOTICE`
`2`                     | `INFO`
`3`                     | `DEBUG`

Log Levels
----------

GNU social supports the logging levels described by [RFC 5424](http://tools.ietf.org/html/rfc5424).

- **DEBUG** (100): Detailed debug information.

- **INFO** (200): Interesting events. Examples: User logs in, SQL logs.

- **NOTICE** (250): Normal but significant events.

- **WARNING** (300): Exceptional occurrences that are not errors. Examples:
  Use of deprecated APIs, poor use of an API, undesirable things that are not
  necessarily wrong.

- **ERROR** (400): Runtime errors that do not require immediate action but
  should typically be logged and monitored.

- **CRITICAL** (500): Critical conditions. Example: Application component
  unavailable, unexpected exception.

- **ALERT** (550): Action must be taken immediately. Example: Entire website
  down, database unavailable, etc. This should trigger the SMS alerts and wake
  you up.

- **EMERGENCY** (600): Emergency: system is unusable.

Using
-----

`Log::level(message: string, context: array);`

* The message MUST be a string or object implementing __toString().

* The message MAY contain placeholders in the form: {foo} where foo
will be replaced by the context data in key "foo".
 
* The context array can contain arbitrary data. The only assumption that
can be made by implementors is that if an Exception instance is given
to produce a stack trace, it MUST be in a key named "exception".

Where Logs are Stored
---------------------

By default, log entries are written to the `var/log/dev.log` file when you’re in the
`dev` environment. In the `prod` environment, logs are written to `var/log/prod.log`,
but only during a request where an error or high-priority log entry was made (i.e. `Log::error()` , `Log::critical()`, `Log::alert()` or `Log::emergency()`).

Example usage
-------------

```php
Log::info('hello, world.');
// Using the logging context, allowing to pass an array of data along the record:
Log::info('Adding a new user', ['username' => 'Seldaek']);
```