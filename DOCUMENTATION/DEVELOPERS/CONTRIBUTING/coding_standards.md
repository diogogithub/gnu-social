GNU social Coding Style
===========================

Please comply with [PSR-2](https://www.php-fig.org/psr/psr-2/) and the following standard when working on GNU social
if you want your patches accepted and modules included in supported releases.

If you see code which doesn't comply with the below, please fix it :)


Strings
-------------------------------------------------------------------------------
Use `'` instead of `"` for strings, where substitutions aren't required.
This is a performance issue, and prevents a lot of inconsistent coding styles.
When using substitutions, use curly braces around your variables - like so:

    $var = "my_var: {$my_var}";


Comments and Documentation
-------------------------------------------------------------------------------
Comments go on the line ABOVE the code, NOT to the right of the code, unless it is very short.
All functions and methods are to be documented using PhpDocumentor - https://docs.phpdoc.org/guides/

File Headers
-------------------------------------------------------------------------------
File headers follow a consistent format, as such:

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

You may find `boilerplate.php` useful when creating a new file from scratch.

Paragraph spacing
-------------------------------------------------------------------------------
Where-ever possible, try to keep the lines to 80 characters.  Don't
sacrifice readability for it though - if it makes more sense to have it in
one longer line, and it's more easily read that way, that's fine.

With assignments, avoid breaking them down into multiple lines unless
neccesary, except for enumerations and arrays.


'If' statements format
-------------------------------------------------------------------------------
Use switch statements where many else if's are going to be used. Switch/case is faster

    if ($var == 'example') {
        echo 'This is only an example';
    } else {
        echo 'This is not a test.  This is the real thing';
    }

Do NOT make if statements like this:

    if ($var == 'example'){ echo 'An example'; }

    OR this

    if($var = 'example')
            echo "An {$var}";


Associative arrays
-------------------------------------------------------------------------------
Always use `[]` instead of `array()`. Associative arrays must be written in the
following manner:

    $array = [
        'var' => 'value',
        'var2' => 'value2'
    ];

Note that spaces are preferred around the '=>'.


A note about shorthands
-------------------------------------------------------------------------------
Some short hands are evil:

- Use the long format for `<?php`.  Do NOT use `<?`.
- Use the long format for `<?php` echo. Do NOT use `<?=`.


Naming conventions
-------------------------------------------------------------------------------
Respect PSR2 first.

- Classes use PascalCase (e.g. MyClass).
- Functions/Methods use camelCase (e.g. myFunction).
- Variables use snake_case (e.g. my_variable).

A note on variable names, etc. It must be possible to understand what is meant
without neccesarialy seeing it in context, because the code that calls something
might not always make it clear.

So if you have something like:

    $notice->post($contents);

Well I can easily tell what you're doing there because the names are straight-
forward and clear.

Something like this:

    foo->bar();

Is much less clear.

Also, whereever possible, avoid ambiguous terms.  For example, don't use text
as a term for a variable.  Call back to "contents" above.


Comparisons
-------------------------------------------------------------------------------
Always use symbol based comparison operators (&&, ||) instead of text based
operators (AND, OR) as they are evaluated in different orders and at different
speeds.  This is will prevent any confusion or strange results.


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
out of why you're nesting it.  It's not always avoidable, but PHP 5 has a lot
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
PHP is a soft-typed language and it falls to us developers to make sure that
we are using the proper inputs.  Where ever possible use explicit type casting.
Where it in't, you're going to have to make sure that you check all your
inputs before you pass them.

All outputs should be cast as an explicit PHP type.

Not properly typecasting is a shooting offence.  Soft types let programmers
get away with a lot of lazy code, but lazy code is buggy code, and frankly, I
don't want it in GNU social if it's going to be buggy.


Consistent exception handling
-------------------------------------------------------------------------------
Consistency is key to good code to begin with, but it is especially important
to be consistent with how we handle errors.  GNU social has a variety of built-
in exception classes.  Use them, wherever it's possible and appropriate, and
they will do the heavy lifting for you.

Additionally, ensure you clean up any and all records and variables that need
cleanup in a function using try { } finally { } even if you do not plan on
catching exceptions (why wouldn't you, though?  That's silly.)

If you do not call an exception handler, you must, at a minimum, record errors
to the log using common_log(level, message)

Ensure all possible control flows of a function have exception handling and
cleanup, where appropriate.  Don't leave endpoints with unhandled exceptions.
Try not to leave something in an error state if it's avoidable.


Return values
-------------------------------------------------------------------------------
All functions must return a value.  Every single one.  This is not optional.

If you are simply making a procedure call, for example as part of a helper
function, then return boolean TRUE on success, and the exception on failure.

When returning the exception, return the whole nine yards, which is to say the
actual PHP exception object, not just an error message.

All return values not the above should be type cast, and you should sanitize
anything returned to ensure it fits into the cast.  You might technically make
an integer a string, for instance, but you should be making sure that integer
SHOULD be a string, if you're returning it, and that it is a valid return
value.

A vast majority of programming errors come down to not checking your inputs
and outputs properly, so please try to do so as best and thoroughly as you can.


Layout and Location of files
-------------------------------------------------------------------------------
`/actions/` contains files that determine what happens when something "happens":
for instance, when someone favourites or repeats a notice.  Code that is
related to a "happening" should go here.

`/classes/` contains abstract definitions of certain "things" in the codebase
such as a user or notice.  If you're making a new "thing", it goes here.

`/lib/` is basically the back-end.  Actions will call something in here to get
stuff done usually, which in turn will probably manipulate information stored
in one or more records represented by a class.

`/extlib/` is where external libraries are located.  If you include a new
external library, it goes here.

`/plugins/` This is a great way to modularize your own new features.  If you want
to create new core features for GNU social, it is probably best to create a
module unless you absolutely must override or modify the core behaviours.
