# Security

## Validate vs Sanitize
You're probably already familiar with the old saying "Never trust your users
input", if not, you're now.

Sadly, that often worries developers so much that they will _sanitize_ every
single user input before storing it. That's, to our eyes, a bad practice.
You shouldn't trust your users, but that should never lead you to break [data integrity](https://en.wikipedia.org/wiki/Data_integrity).

Instead of sanitize before store, you should _validate_ if the input makes sense,
and tell your client if it isn't.

## Sanitize before spitting out

If a user inputs a string containing HTML tags, you shouldn't strip them out
before storing. Depending on the context, you should sanitize it before
outputting. For that you can call `App\Core\Security::sanitize(string: $html)`,
optionally you can send a second argument specifying tags to maintain `array: ['tag']`.

## Generating a readable confirmation code
TODO