# Exception handling

[Exceptions are a control-flow mechanism](https://en.wikipedia.org/wiki/Exception_handling). The motivation for this
control-flow mechanism, was specifically separating error handling from non-error handling code. In the common case
that error handling is very repetitive and bears little relevance to the main part of the logic.

The [exception safety](https://en.wikipedia.org/wiki/Exception_safety) level adopted in GNU social is _Strong_, which
makes use of commit or rollback semantics: Operations can fail, but failed operations are guaranteed to have no side
effects, leaving the original values intact.

In GNU social, exceptions thrown by a function should be part of its declaration.
They are part of the [contract](https://en.wikipedia.org/wiki/Design_by_contract) defined by its interface:
This function does A, or fails because of B or C.

> N.B.: An error or exception means that the function cannot achieve its advertised purpose.
> You should never base your logic around a function's exception behaviour.

PHP has concise ways to call a function that returns multiple values (arrays/lists) and I/O parameters so, do not be
tempted to use Exceptions for the purpose. Exceptions are exceptional cases and not part of a regular flow.

Furthermore, do not use [error codes](https://en.wikipedia.org/wiki/Error_code), that's not how we handle errors in
GNU social. E.g., if you return 42, 1337, 31337, etc. values instead of `FileNotFoundException`, that function can not
be easily understood.

## Why different exceptions?

What can your caller do when he receives an exception? It makes sense to have different exception classes, so the
caller can decide whether to retry the same call, to use a different solution (e.g., use a fallback source instead),
or quit.

## Hierarchy
GNU social has two exception hierarchies:

- Server exceptions: For the most part, the hierarchy beneath this class should be broad, not deep. You'll probably
  want to log these with a good level of detail.
- Client exceptions: Used to inform the end user about the problem of his input. That means creating a user-friendly
  message. These will hardly be relevant to log.

Do not extend the PHP Exception class, always extend a derivative of one of these two root exception classes.

* [Exception (from PHP)](https://www.php.net/manual/en/language.exceptions.php)
    - ClientException (Indicates a client request contains some sort of error. HTTP code 400.)
        * InvalidFormException (Invalid form submitted.)
        * NicknameException (Nickname empty exception.)
            - NicknameEmptyException
            - NicknameInvalidException
            - NicknameReservedException
            - NicknameTakenException
            - NicknameTooLongException
            - NicknameTooShortException

    - ServerException
        * DuplicateFoundException (Duplicate value found in DB when not expected.)
        * NoLoggedInUser (No user logged in.)
        * NotFoundException
        * TemporaryFileException (TemporaryFile errors.)
            - NoSuchFileException (No such file found.)
            - NoSuchNoteException (No such note.)


## General recommendations

(Adapted from http://codebuild.blogspot.com/2012/01/15-best-practices-about-exception.html)

* In the general case you want to keep your exceptions broad but not too broad. You only need to deepen it in
  situations where it is useful to the caller. For example, if there are five reasons a message might fail from client
  to server, you could define a ServerMessageFault exception, then define an exception class for each of those five
  errors beneath that. That way, the caller can just catch the superclass if it needs or wants to. Try to limit this
  to specific, reasonable cases.
* Deal with errors/exceptions at the appropriate level. If lower in the call stack, awesome. Quite often, the most
  appropriate is a much higher level.
* Don't manage logic with exceptions: If a control can be done with if-else statement clearly, don't use exceptions
  because it reduces readability and performance (e.g., null control, divide by zero control).
* Exception names must be clear and meaningful, stating the causes of exception.
* Catch specific exceptions instead of the top Exception class. This will bring additional performance, readability and
  more specific exception handling.
* Try not to re-throw the exception because of the price. If re-throwing had been a must, re-throw the same exception
  instead of creating a new one. This will bring additional performance. You may add additional info in each layer
  to that exception.
* Always clean up resources (opened files etc.) and perform this in ["finally" blocks](https://www.php.net/manual/en/language.exceptions.php#language.exceptions.finally).
* Don't absorb exceptions with no logging and operation. Ignoring exceptions will save that moment but will create a
  chaos for maintainability later.
* Exception handling inside a loop is not recommended for most cases. Surround the loop with exception block instead.
* Granularity is very important. One try block must exist for one basic operation. So don't put hundreds of lines in a
  try-catch statement.
* Produce enough documentation for your exception definitions
* Don't try to define all of your exception classes before they are actually used. You'll wind up re-doing most of it.
  When you encounter an error case while writing code, then decide how best to describe that error. Ideally, it should
  be expressed in the context of what the caller is trying to do.
  
