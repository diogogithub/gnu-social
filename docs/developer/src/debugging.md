Debugging and Testing
=====================

Testing
-------

GNU social isn't too big or hard to understand, but remember that
events are asynchronous and every handler must take care not to
interfere with core and other plugins normal behaviours.

We encourage [Test-driven development](https://en.wikipedia.org/wiki/Test-driven_development),
as it helps preventing regressions and unexpected behaviour.

To run GNU social's tests you can execute:

```sh
make tests
```

To write your own `TestCase` you can `use App\Util\GNUsocialTestCase`.

To mock HTTP requests you can `$client = static::createClient();`.

Finally, to use services such as queues you must `parent::bootKernel();`.

As the test framework we adopted PHPUnit, you have a list of possible assertions
in [PHPUnit Manual](https://phpunit.readthedocs.io/en/9.5/).

Debugging
---------

Because we are using Symfony, we recall that a useful tool for debugging
is [Symfony's VarDumper component](https://symfony.com/doc/current/components/var_dumper.html),
as a more friendly alternative to PHP's `var_dump` and `print_r`.