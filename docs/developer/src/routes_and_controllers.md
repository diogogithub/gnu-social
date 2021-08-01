# Routes and Controllers
## Routes
When GNU social receives a request, it calls a
controller to generate the response. The routing
configuration defines which action to run for each
incoming URL.

You create routes by handling the `AddRoute` event.

```php
public function onAddRoute(RouteLoader $r)
{
    $r->connect('avatar', '/{gsactor_id<\d+>}/avatar/{size<full|big|medium|small>?full}',
    [Controller\Avatar::class, 'avatar_view']);
    $r->connect('settings_avatar', '/settings/avatar',
    [Controller\Avatar::class, 'settings_avatar']);
    return Event::next;
}
```

The magic goes on `$r->connect((string $id, string $uri_path, array|string $target, array $param_reqs = [], array $accept_headers = [], array $options = []))`.
Here how it works:
* `id`: an identifier for your route so that you can easily refer to it later;
* `uri_path`: the url to be matched, can be static or have parameters;
   The variable parts are wrapped in `{...}` and they must have a unique name;
* `target`: Can be an array _[Class, Method to invoke]_ or a string with _Class_ to __invoke;
* `param_reqs`: You can either do `['parameter_name' => 'regex']` or write the requirement inline `{parameter_name<regex>}`;
* `accept_headers`: If `[]` then the route will accept any [HTTP Accept](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept).
   If the route should only be used for certain Accept headers, then specify an array of the form: `['content type' => q-factor weighting]`;
* `options['format']`: Response content-type;
* `options['conditions']`: https://symfony.com/doc/current/routing.html#matching-expressions ; 
* `options['template']`: Render a twig template directly from the route.

### Observations

* The special parameter `_format` can be used to set the "request format" of the Request object. This is used for such things as setting the Content-Type of the response (e.g. a json format translates into a Content-Type of application/json).
  This does _not_ override the `options['format']` nor the `HTTP Accept header` information.
```php
$r->connect(id: 'article_show', uri_path: '/articles/search.{_format}',
    target: [ArticleController::class, 'search'],
    param_reqs: ['_format' => 'html|xml']
);
```
* An example of a suitable accept headers array would be:
```php
$acceptHeaders = [
    'application/ld+json; profile="https://www.w3.org/ns/activitystreams"' => 0,
    'application/activity+json' => 1,
    'application/json' => 2,
    'application/ld+json' => 3
];
```

## Controllers

A controller is a PHP function you create that reads information from the Request object and creates and returns a
either a Response object or an array that merges with the route `options` array.
The response could be an HTML page, JSON, XML, a file download, a redirect, a 404 error or anything else.

### HTTP method

```php
/**
* @param Request $request
* @param array $vars Twig Template vars and route options
*/
public function onGet(Request $request, array $vars): array|Response
{
    return 
}
```

### Forms

```php
public function settings_avatar(Request $request): array
{
    $form = Form::create([
        ['avatar', FileType::class,     ['label' => _m('Avatar'), 'help' => _m('You can upload your personal avatar. The maximum file size is 2MB.'), 'multiple' => false, 'required' => false]],
        ['remove', CheckboxType::class, ['label' => _m('Remove avatar'), 'help' => _m('Remove your avatar and use the default one'), 'required' => false, 'value' => false]],
        ['hidden', HiddenType::class,   []],
        ['save',   SubmitType::class,   ['label' => _m('Submit')]],
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $data       = $form->getData();
        $user       = Common::user();
        $gsactor_id = $user->getId();
        // Do things
    }

    return ['_template' => 'settings/avatar.html.twig', 'avatar' => $form->createView()];
}
```