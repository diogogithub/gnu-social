# Queue

Some activities that GNU social can do, like broadcasting with OStatus or
ActivityPub, XMPP messages and SMS operations, can be 'queued' and done by
asynchronous daemons instead.

## Running Queues

Run the queue handler with:

```sh
php bin/console messenger:consume async --limit=10 --memory-limit=128M --time-limit=3600
```

GNU social uses Symfony, therefore the [documentation on
queues](https://symfony.com/doc/current/messenger.html#deploying-to-production)
might be useful.

## Definitions

* Message - A Message holds the data to be handled (a variable) and the queue name (a string). 
* QueueHandler - A QueueHandler is an event listener that expects to receive data from the queue.
* Enqueuer - An Enqueuer is any arbitrary code that wishes to send data to a queue.
* Transporter - The Transporter is given a Message by an Enqueuer. The Transporter is responsible
  for ensuring that the Message is passed to all relevant QueueHandlers.

## Using Queues

Queues are akin to events.

In your plugin you can call `App\Core\Queue::enqueue` and send a message to
be handled by the queue:

```php
Queue::enqueue($hello_world, 'MyFirstQueue');
```

and then receive with:

```php
public function onMyFirstQueue($data): bool
{
    // Do something with $data
    return Event::next;
}
```

GNU social comes with a set of core queues with often wanted data: TODO Elaborate.