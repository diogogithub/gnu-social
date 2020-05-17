### Entrypoint

GNU social's entrypoint is the `public/index.php` script, which gets
called by the webserver for all requests. This is handled by the
webserver itself, which translates a `GET /foo` to `GET
/index.php?p=foo`. This feature is called 'fancy URLs', as it was in V2.

The `index` script handles all the initialization of the Symfony
framework and social itself. It reads configuration from `.env` or any
`.env.*` file at the project root. The `index` script creates a
`Kernel` object, which is defined on the `src/Kernel.php` file. This
is the part where the code we control starts; the `Kernel` constructor
creates the needed constants, sets the timezone to UTC and the string
encoding to UTF8. The other functions in this class get called by the
Symfony framework at the appropriate times. We will come back to this
file.

### Registering services

Next, the `src/Util/GNUsocial.php` class is instantiated by the
Symfony framework, on the `'onKernelRequest'` or `'onCommand'` events. The
former event, as described in the docs:

> This event is dispatched very early in Symfony, before the
> controller is determined. It's useful to add information to the
> Request or return a Response early to stop the handling of the
> request.

The latter, is launched on the `bin/console` script is used.

In both cases, these events call the `register` function, which
creates static references for the logging, event and translation
services. This is done so these services can be used via static
function calls, which is much less verbose and more accessible than
the way the framework recommends. This function also loads all the
Modules and Plugins, which like in V2, are components that aren't
directly connected to the core code, being used to implement internal
and optional functionality respectively, by handling events launched
by various parts of the code.

### Database definitions

Going back to the `Kernel`, the `build` function gets called by the
Symfony framework and allows us to register a 'Compiler Pass'.
Specifically, we register the `SchemaDefPass` from the
`src/DependencyInjection/Compiler/SchemaDefPass.php` file, which adds
a new 'metadata driver' to Doctrine. The metadata driver is
responsible for loading database definitions. We keep the same method
as in V2, which was that each 'Entity' has a `schemaDef` static
function which returns an array describing the database.

This definition is handled by the `SchemaDefDriver` class from
`src/Util/SchemaDefDriver.php` file, which extends `StaticPHPDriver`
and replaces the methods `loadMetadata` with `schemaDef`. The function
`loadMetadataForClass` function is called by the Symfony framework for
each file in `src/Entity/`. It allows us to call the `schemaDef`
function and translate the array definition to Doctrine's internal
representation.

### Routing

Next, we'll look at the `RouteLoader`, defined in
`src/Util/RoutLoader.php`, which loads all the files from
`src/Routes/*.php` and calls the static `load` method, which defines
routes with an interface similar to V2's `connect`, except it requires
an extra identifier as the first argument. This identifier is used,
for instance, to generate URLs for each route. Each route connects an
URL path to a Controller, with the possibility of taking arguments,
which are passed to the `__invoke` method of the respective
controller. The controllers are defined in `src/Controller/` and are
responsible for handling a request and return a Symfony `Response`
object (subject to change, in order to abstract HTML vs JSON output).

### End to end

The next steps are handled by the Symfony framework which creates a
`Request` object from the HTTP request, and then a corresponding
`Response` is created by calling the `Kernel::handle` method, which
matches the appropriate route and thus calls it's controller.

### Performance

All this happens on each request, which seems like a lot to handle,
and would be too slow. Fortunately, Symfony has a 'compiler' which
caches and optimizes the code paths. In production mode, this can be
done through a command, while in development mode, it's handled on
each request if the file changed, which has a performance impact, but
obviously makes development easier.
