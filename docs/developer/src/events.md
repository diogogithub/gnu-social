Events and event handlers
=========================

Definitions (adapted from PSR-14)
-----------

* Event - An Event is a message produced by an Emitter. Usually denoting a [state](https://en.wikipedia.org/wiki/State_(computer_science)) change. 
* Listener - A Listener is any [PHP callable](https://www.php.net/manual/en/language.types.callable.php) that expects to
  be passed an Event. Zero or more Listeners may be passed the same Event. A Listener MAY enqueue some other asynchronous
  behavior if it so chooses.
* Emitter - An Emitter is any arbitrary code that wishes to dispatch an Event. This is also known as the "calling code".
* Dispatcher - The Dispatcher is given an Event by an Emitter. The Dispatcher is responsible for ensuring that the Event
  is passed to all relevant Listeners.

Pattern
-------

We implement the [Observer pattern](https://en.wikipedia.org/wiki/Observer_pattern) using the [Mediator pattern](https://en.wikipedia.org/wiki/Mediator_pattern).

The key is that the emitter should not know what is listening to its events. The dispatcher avoids modules communicate
directly but instead through a mediator. This helps the [Single Responsibility principle](https://en.wikipedia.org/wiki/Single-responsibility_principle)
by allowing communication to be offloaded to a class that just handles communication.

How does it work? The *dispatcher*, the central object of the event dispatcher system, notifies *listeners* of an *event*
dispatched to it. Put another way: your code dispatches an event to the dispatcher, the dispatcher notifies all registered
listeners for the event, and each listener does whatever it wants with the event.

Example 1: Adding elements to the core UI
-------

> An emitter in a core twig template

```html
{% for block in handle_event('ViewAttachment' ~ attachment.getMimetypeMajor() | capitalize , {'attachment': attachment, 'thumbnail_parameters': thumbnail_parameters}) %}
    {{ block | raw }}
{% endfor %}
```

> Listener

```php
/**
 * Generates the view for attachments of type Image
 *
 * @param array $vars Input from the caller/emitter
 * @param array $res I/O parameter used to accumulate or return values from the listener to the emitter
 *
 * @return bool true if not handled or if the handling should be accumulated with other listeners,
 *              false if handled well enough and no other listeners are needed
 */
public function onViewAttachmentImage(array $vars, array &$res): bool
{
    $res[] = Formatting::twigRenderFile('imageEncoder/imageEncoderView.html.twig', ['attachment' => $vars['attachment'], 'thumbnail_parameters' => $vars['thumbnail_parameters']]);
    return Event::stop;
}
```

Some things to note about this example:
* The parameters of the handler `onViewAttachmentImage` are defined by the emitter;
* Every handler must return a bool stating what is specified in the example docblock.

Example 2: Informing the core about an handler
-------

> Event emitted in the core 

```php
Event::handle('ResizerAvailable', [&$event_map]);
```

> Event lister in a plugin

```php
/**
 * @param array $event_map output
 *
 * @return bool event hook
 */
public function onResizerAvailable(array &$event_map): bool
{
    $event_map['image'] = 'ResizeImagePath';
    return Event::next;
}
```