# GNU social at a high level

GNU social's execution begins at `public/index.php`, which gets
called by the webserver for all requests. This is handled by the
webserver itself, which translates a `GET /foo` to `GET
/index.php?p=foo`. This feature is called 'fancy URLs', as it was in V2.

The `index` script handles all the initialization of the Symfony
framework and social itself. It reads configuration from `.env` or any
`.env.*`, as well as `social.yaml` and `social.local.yaml` files at
the project root. The `index` script creates a `Kernel` object, which
is defined in `src/Kernel.php`. This is the part where the code we
control starts; the `Kernel` constructor creates the needed constants,
sets the timezone to UTC and the string encoding to UTF8. The other
functions in this class get called by the Symfony framework at the
appropriate times. We will come back to this file.

### Registering services

Next, the `src/Util/GNUsocial.php` class is instantiated by the
Symfony framework, on the `'onKernelRequest'` or `'onCommand'` events. The
former event, as described in the docs:

> This event is dispatched very early in Symfony, before the
> controller is determined. It's useful to add information to the
> Request or return a Response early to stop the handling of the
> request.

The latter, is launched when the `bin/console` script is used.

In both cases, these events call the `register` function, which
creates static references for the services such as logging, event and
translation. This is done so these services can be used via static
function calls, which is much less verbose and more accessible than
the way the framework recommends. This function also loads all the
Components and Plugins, which like in V2, are modules that aren't
directly connected to the core code, being used to implement internal
and optional functionality respectively, by handling events launched
by various parts of the code.

### Database definitions

Going back to the `Kernel`, the `build` function gets called by the
Symfony framework and allows us to register a 'Compiler Pass'.
Specifically, we register
`App\DependencyInjection\Compiler\SchemaDefPass` and
`App\DependencyInjection\Compiler\ModuleManagerPass`. The former adds
a new 'metadata driver' to Doctrine. The metadata driver is
responsible for loading database definitions. We keep the same method
as in V2, where each 'Entity' has a `schemaDef` static function which
returns an array with the database definition. The latter handles the
loading of modules (components and plugins).

This datbase definition is handled by the `SchemaDefPass` class, which
extends `Doctrine\Persistence\Mapping\Driver\StaticPHPDriver`. The
function `loadMetadataForClass` is called by the Symfony
framework for each file in `src/Entity/`. It allows us to call the
`schemaDef` function and translate the array definition to Doctrine's
internal representation. The `ModuleManagerPass` later uses this class
to load the entity definitions from each plugin.

### Routing

Next, we'll look at the `RouteLoader`, defined in
`src/Util/RoutLoader.php`, which loads all the files from
`src/Routes/*.php` and calls the static `load` method, which defines
routes with an interface similar to V2's `connect`, except it requires
an extra identifier as the first argument. This identifier is used,
for instance, to generate URLs for each route. Each route connects an
URL path to a Controller, with the possibility of taking arguments,
which are passed to the `__invoke` method of the respective controller
or the given method. The controllers are defined in `src/Controller/`
or `plugins/*/Controller` or `components/*/Controller` and are
responsible for handling a request and return a Symfony `Response`
object or an array that gets converted to one (subject to change, in
order to abstract HTML vs JSON output).

This array conversion is handled by `App\Core\Controller`, along with
other aspects, such as firing events we use. It also handles
responding with the appropriate requested format, such as HTML or
JSON, with what the controller returned.

### End to end

The next steps are handled by the Symfony framework which creates a
`Request` object from the HTTP request, and then a corresponding
`Response` is created by `App\Core\Controller`, which matches the
appropriate route and thus calls it's controller.

### Performance

All this happens on each request, which seems like a lot to handle,
and would be too slow. Fortunately, Symfony has a 'compiler' which
caches and optimizes the code paths. In production mode, this can be
done through a command, while in development mode, it's handled on
each request if the file changed, which has a performance impact, but
obviously makes development easier. In addition, we cache all the
module loading.
