GNU social Coding Style
===========================

Please comply with [PSR-12](https://www.php-fig.org/psr/psr-12/) and the following standard when working on GNU social
if you want your patches accepted and modules included in supported releases.

If you see code which doesn't comply with the below, please fix it :)

Programming Paradigms
-------------------------------------------------------------------------------

GNU social is written with [multiple programming paradigms](https://en.wikipedia.org/wiki/Programming_paradigm#Multi-paradigm)
in different places.

Most of GNU social code is [_procedural programming_](https://en.wikipedia.org/wiki/Procedural_programming) contained in
functions whose name starts with `on`. Starting with "on" is making use of the [Event](https://en.wikipedia.org/wiki/Event-driven_programming)
dispatcher (`onEventName`).
This allows for a [_declarative_](https://en.wikipedia.org/wiki/Declarative_programming) structure.

Hence, the most common function structure is the one in the following example:

```php
public function onRainStart(array &$args): bool
{
    Util::openUmbrella();
    return true;
}
```

Things to note in the example above:
* This function will be called when the event "RainStart" is dispatched, thus its declarative nature.
  More on that in the [Events chapter](./events.md).
* We call a static function from a `Util` class. That's often how we use classes in GNU social.
  A notable exception being Entities. More on that in the [Database chapter](./database.md).

It's also common to have [functional code](https://en.wikipedia.org/wiki/Functional_programming) snippets
in the middle of otherwise entirely imperative blocks (e.g., for handling list manipulation).
For this we often use the library [Functional PHP](https://github.com/lstrojny/functional-php/). 

Use of [reflective programming](https://en.wikipedia.org/wiki/Reflective_programming#PHP),
[variable functions](https://www.php.net/manual/en/functions.variable-functions.php), and
[magic methods](https://www.php.net/manual/en/language.oop5.magic.php) are sometimes employed in the core.
These principles defy what is then adopted and recommended out of the core (components, plugins, etc.).
The core is a lower level part of GNU social that carefully takes advantage of these resources.
Unless contributing to the core, you most likely _shouldn't_ use these.

PHP allows for a high level of code expression. In GNU social we have conventions for when each programming style
should be adopted as well as methods for handling some common operations. Such an example is string parsing: We never
chain various `substring` calls. We write a [regex](https://en.wikipedia.org/wiki/Regular_expression) pattern and then
call `preg_match` instead.  All of this consistency highly contributes for a more readable and easier of maintaining code.

Strings
-------------------------------------------------------------------------------
Use `'` instead of `"` for strings, where substitutions aren't required.
This is a performance issue, and prevents a lot of inconsistent coding styles.
When using substitutions, use curly braces around your variables - like so:

```php
$var = "my_var: {$my_var}";
```


Comments and Documentation
-------------------------------------------------------------------------------
Comments go on the line ABOVE the code, NOT to the right of the code, unless it is very short.
All functions and methods are to be documented using PhpDocumentor - https://docs.phpdoc.org/guides/

File Headers
-------------------------------------------------------------------------------
File headers follow a consistent format, as such:

```php
 // This file is part of GNU social - https://www.gnu.org/software/social
 //
 // GNU social is free software: you can redistribute it and/or modify
 // it under the terms of the GNU Affero General Public License as published by
 // the Free Software Foundation, either version 3 of the License, or
 // (at your option) any later version.
 //
 // GNU social is distributed in the hope that it will be useful,
 // but WITHOUT ANY WARRANTY; without even the implied warranty of
 // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 // GNU Affero General Public License for more details.
 //
 // You should have received a copy of the GNU Affero General Public License
 // along with GNU social.  If not, see <http://www.gnu.org/licenses/>.

 /**
  * Description of this file.
  *
  * @package   samples
  * @author    Diogo Cordeiro <diogo@fc.up.pt>
  * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
  * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
  */
```

Please use it.

A few notes:

*  The description of the file doesn't have to be exhaustive.  Rather it's
   meant to be a short summary of what's in this file and what it does.  Try
   to keep it to 1-5 lines.  You can get more in-depth when documenting
   individual functions!

*  You'll probably see files with multiple authors, this is by
   design - many people contributed to GNU social or its forebears!  If you
   are modifying an existing file, APPEND your own author line, and update
   the copyright year if needed. Do not replace existing ones.

Paragraph spacing
-------------------------------------------------------------------------------
Where-ever possible, try to keep the lines to 80 characters.  Don't
sacrifice readability for it though - if it makes more sense to have it in
one longer line, and it's more easily read that way, that's fine.

With assignments, avoid breaking them down into multiple lines unless
neccesary, except for enumerations and arrays.


'If' statements format
-------------------------------------------------------------------------------
Use switch statements where many else if's are going to be used. Switch/case is faster.

```php
 if ($var === 'example') {
     echo 'This is only an example';
 } else {
     echo 'This is not a test.  This is the real thing';
 }
```

Do NOT make if statements like this:

```php
 if ($var === 'example'){ echo 'An example'; }
```

 OR this

```php
 if ($var === 'example')
         echo "An {$var}";
```

Associative arrays
-------------------------------------------------------------------------------
Always use `[]` instead of `array()`. Associative arrays must be written in the
following manner:

```php
 $array = [
     'var' => 'value',
     'var2' => 'value2'
 ];
```

Note that spaces are preferred around the '=>'.


A note about shorthands
-------------------------------------------------------------------------------
Some short hands are evil:

- Use the long format for `<?php`.  Do NOT use `<?`.
- Use the long format for `<?php` echo. Do NOT use `<?=`.


Naming conventions
-------------------------------------------------------------------------------
Respect PSR-12 first.

- Classes use PascalCase (e.g. `MyClass`).
- Functions/Methods use camelCase (e.g. `myFunction`).
- Variables use snake_case (e.g. `my_variable`).

A note on variable names, etc. It must be possible to understand what is meant
without necessarily seeing it in context, because the code that calls something
might not always make it clear.

So if you have something like:

```php
 $notice->post($contents);
```

Well I can easily tell what you're doing there because the names are straight-
forward and clear.

Something like this:

```php
 foo->bar();
```

Is much less clear.

Also, wherever possible, avoid ambiguous terms.  For example, don't use text
as a term for a variable.  Call back to "contents" above.


Arrays
-------------------------------------------------------------------------------
Even though PSR-12 doesn't specifically specify rules for array formatting, it
is in the spirit of it to have every array element on a new line like is done
for function and class method arguments and condition expressions, if there is
more than one element.
In this case, even the last element should end on a comma, to ease later
element addition.

```php
 $foo = ['first' => 'unu'];
 $bar = [
     'first'  => 'once',
     'second' => 'twice',
     'third'  => 'thrice',
 ];
 ```


Comparisons
-------------------------------------------------------------------------------
Always use symbol based comparison operators (&&, ||) instead of text based
operators (and, or) in an "if" clause as they are evaluated in different order
and at different speeds.
This is will prevent any confusion or strange results.

Prefer using `===` instead of `==` when possible. Version 3 started with PHP 8,
use strict typing whenever possible. Using strict comparisons takes good
advantage of that.


Use English
-------------------------------------------------------------------------------
All variables, classes, methods, functions and comments must be in English.
Bad english is easier to work with than having to babelfish code to work out
how it works.


Encoding
-------------------------------------------------------------------------------
Files should be in UTF-8 encoding with UNIX line endings.


No ending tag
-------------------------------------------------------------------------------
Files should not end with an ending php tag "?>".  Any whitespace after the
closing tag is sent to the browser and cause errors, so don't include them.


Nesting Functions
-------------------------------------------------------------------------------
Avoid, if at all possible.  When not possible, document the living daylights
out of why you're nesting it.  It's not always avoidable, but PHP has a lot
of obscure problems that come up with using nested functions.

If you must use a nested function, be sure to have robust error-handling.
This is a must and submissions including nested functions that do not have
robust error handling will be rejected and you'll be asked to add it.


Scoping
-------------------------------------------------------------------------------
Properly enforcing scope of functions is something many PHP programmers don't
do, but should.

In general:
*  Variables unique to a class should be protected and use interfacing to
   change them.  This allows for input validation and making sure we don't have
   injection, especially when something's exposed to the API, that any program
   can use, and not all of them are going to be be safe and trusted.

*  Variables not unique to a class should be validated prior to every call,
   which is why it's generally not a good idea to re-use stuff across classes
   unless there's significant performance gains to doing so.

*  Classes should protect functions that they do not want overriden, but they
   should avoid protecting the constructor and destructor and related helper
   functions as this prevents proper inheritance.


Typecasting
-------------------------------------------------------------------------------
PHP is a soft-typed language, it falls to us developers to make sure that
we are using the proper inputs.  When possible, use explicit type casting.
Where it isn't, you're going to have to make sure that you check all your
inputs before you pass them.

All inputs should be cast as an explicit PHP type.

Not properly typecasting is a shooting offence.  Soft types let programmers
get away with a lot of lazy code, but lazy code is buggy code, and frankly, we
don't want it in GNU social if it's going to be buggy.


Consistent exception handling
-------------------------------------------------------------------------------
Consistency is key to good code to begin with, but it is especially important
to be consistent with how we handle errors.  GNU social has a variety of built-
in exception classes.  Use them, wherever it's possible and appropriate, and
they will do the heavy lifting for you.

Additionally, ensure you clean up any and all records and variables that need
cleanup in a function using try { } finally { } even if you do not plan on
catching exceptions (why wouldn't you, though?  That's silly.).

If you do not call an exception handler, you must, at a minimum, record errors
to the log using `Log::level(message)`.

Ensure all possible control flows of a function have exception handling and
cleanup, where appropriate.  Don't leave endpoints with unhandled exceptions.
Try not to leave something in an error state if it's avoidable.

NULL, VOID and SET
-------------------------------------------------------------------------------

When programming in PHP it's common having to represent the absence of value.
A variable that wasn't initialized yet or a function that could not produce a value.
On the latter, one could be tempted to throw an exception in these scenarios, but not always that kind
of failure fits the panic/exception/crash category.

On the discussion of whether to **use `=== null` vs [`is_null()`](https://www.php.net/manual/en/function.is-null.php)**,
the literature online is diverse and divided. We conducted an [internal poll](https://agile.gnusocial.rocks/doku.php?id=php_null)
and the winner was `is_null()`.

Some facts to consider:
1. [null is both a data type, and a value](https://www.php.net/manual/en/language.types.null.php);
2. As noted in PHP's documentation, the constant null forces a variable to be of type null;
3. A variable with null value returns false in an [isset()](https://www.php.net/manual/en/function.isset.php) test,
  despite that, assigning a variable to NULL is _not_ the same as [unsetting](https://www.php.net/manual/en/function.unset.php) it.
  To actually test whether a variable is set or not [requires adopting different strategies per context (https://stackoverflow.com/a/18646568)](https://web.archive.org/web/20161001180951/https://stackoverflow.com/questions/418066/best-way-to-test-for-a-variables-existence-in-php-isset-is-clearly-broken/18646568#answer-18646568). 
4. The [void return type](https://wiki.php.net/rfc/void_return_type) doesn't return NULL, but if used as
  an expression, it evaluates to null.
  
Considering union types and what we use `null` to represent, we believe that our use of null is always akin to that of
a [Option type](https://en.wikipedia.org/wiki/Option_type). Here's an example:

```php
function sometimes_has_answer(): ?int
{
    return random_int(1, 100) < 50 ? 42 : null;
}

$answer = sometimes_has_answer();
if (!is_null($answer)) {
    echo "Hey, we've got an {$answer}!";
} else {
    echo 'Sorry, no value. Better luck next time!';
}
```

A non-void function, by definition, is expected to return a value.
If it couldn't and didn't run on an exceptional scenario, then you should test in a different style from that of regular
strict comparison. Hence, as you're testing whether a variable is of type null, then you should use `is_null($var)`.
Just as you normally would with an `is_int($var)`  or `is_countable($var)`.
  
About [nullable types](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.union.nullable),
we prefer that you _use_ the shorthand `?T` instead of the full form `T|null` as it suggests that you're considering the
possibility of not having the value of a certain variable. This apparent intent is reinforced by the fact that NULL can
not be a standalone type in PHP.