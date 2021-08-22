Initialization
--------------

Plugins overload this method to do any initialization they need, like connecting
to remote servers or creating paths or so on. @return bool hook value; true
means continue processing, false means stop.

```php
public function initialize(): bool
{
    return true;
}
```

Clean Up
--------

Plugins overload this method to do any cleanup they need, like disconnecting from
remote servers or deleting temp files or so on.

```php
public function cleanup(): bool
{
    return true;
}
```
