# Developing Modules
By now you should have already read on how to interact with GNU social's internals.

So now you want to include your own functionality. For that you can create a plugin or
replace a component.

## Location

* Third party plugins are placed in `local/plugins`.
* Third party components are placed in `local/components`.

## Structure

The structure of a module is similar to that of the core. The tree is

```
local/plugins/Name
├── composer.json  : Local composer configuration for the module
├── config.yaml    : Default configuration for the module
├── locale         : Translation files for the module
├── Name.php       : Each plugin requires a main class to interact with the GNU social system
├── README.md      : A good plugin is a documented one :)
├── src            : Some plugins need more than the main class
│   ├── Controller
│   ├── Entity
│   └── Util
│       └── Exception : A sophisticated plugin may require some internal exceptions, these should extend GNUU social's own exceptions
├── templates
│   ├── Name       : In case the plugin adds visual elements to the UI
└── tests          : Just because it is a plugin, it doesn't mean it should be equally tested!
```

You don't need all of these directories or files. But you do have to follow this
structure in order to have the core autoload your code.

To make a plugin, the file `Name.php` has to extend `App\Core\Modules\Plugin`.

To make a component, the file `Name.php` has to extend `App\Core\Modules\Component`.

As with components, some plugins have to follow special APIs in order to successfully
provide certain functionality. Under `src/Core/Modules` you'll find some abstract
classes that you can extend to implement it properly.

## The main class

The plugin's main class handles events with `onEventName` and should implement the
function `version` to inform the plugin's current version as well as handle the event
`onPluginVersion` to add the basic metadata:

```php
/**
 * @return string Current plugin version
*/
public function version(): string
{
    return '0.1.0';
}

/**
 * Event raised when GNU social polls the plugin for information about it.
 * Adds this plugin's version information to $versions array
 *
 * @param &$versions array inherited from parent
 *
 * @return bool true hook value
 */
public function onPluginVersion(array &$versions): bool
{
    $versions[] = [
        'name'        => 'PluginName',
        'version'     => $this->version(),
        'author'      => 'Author 1, Author 2',
        'homepage'    => 'https://gnudev.localhost/',
        'description' => // TRANS: Plugin description.
            _m('Describe this awesome plugin.'),
    ];
    return Event::next;
}
```