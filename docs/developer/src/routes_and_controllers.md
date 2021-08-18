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

The magic goes on `$r->connect(string $id, string $uri_path, $target, ?array $options = [], ?array $param_reqs = [])`.
Here how it works:
* `id`: a unique identifier for your route so that you can easily refer to it later, for instance when generating URLs;
* `uri_path`: the url to be matched, can be static or have parameters. The variable parts are wrapped in `{...}` and they must have a unique name;
* `target`: Can be an array _[Class, Method to invoke]_ or a string with _Class_ to __invoke;
* `param_reqs`: You can either do `['parameter_name' => 'regex']` or write the requirement inline `{parameter_name<regex>}`;
* `options['accept']`: The Accept header values this route will match with;
* `options['format']`: Response content-type;
* `options['conditions']`: https://symfony.com/doc/current/routing.html#matching-expressions ; 
* `options['template']`: Render a twig template directly from the route.

### Observations

* The special parameter `_format` can be used to set the "request format" of the Request object. This is used for such things as setting the Content-Type of the response (e.g. a json format translates into a Content-Type of application/json).
  This does _not_ override the `options['format']` nor the `HTTP Accept header` information.
```php
$r->connect(id: 'article_show', uri_path: '/articles/search.{format}',
    target: [ArticleController::class, 'search'],
    param_reqs: ['format' => 'html|xml']
);
```
* An example of a suitable accept headers array would be:
```php
$r->connect('json_test', '/json_only', [C\JSON::class, 'test'], options: [
    'accept' => [
        'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
        'application/activity+json',
        'application/json',
        'application/ld+json'
    ]]);
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
