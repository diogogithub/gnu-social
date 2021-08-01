# The Core
This documentation adopted a top-down approach. We believed this to be the most helpful as it reduces the time needed
to start developing third party plugins. To contribute to GNU social's core, on the other hand, it's important to
[understand its flows](./core.md) and internals well.

The `core` tries to be minimal. The essence of it being various wrappers around Symfony. It is divided in:

- [Modules](./core/modules.md);
- [Event dispatcher](core/events.md);
- [ORM and Caching](./core/orm_and_caching.md);
- [Interfaces](./core/interfaces.md);
- [UI](./core/ui.md);
- [Internationalization](core/i18n.md);
- [Utils](./core/util.md);
- [Queues](./core/queues.md);
- [Files](./core/files.md);
- [Sessions and Security](./core/security.md);
- [HTTP Client](./core/http.md).
- [Exceptions](./core/exception_handler.md).