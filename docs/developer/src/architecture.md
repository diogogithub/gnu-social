# Architecture

## Core

The `core` tries to be minimal. The essence of it being various wrappers around Symfony. It provides:

- the Module system described in this chapter;
- [Events](./events.md);
- data representation via an [Object-relational database](https://en.wikipedia.org/wiki/Object%E2%80%93relational_database),
which is elaborated in [Database](./database.md);
- [Cache](./cache.md);
- [Routes and Controllers](./routes_and_controllers.md);
- [Templates](./templates.md);
- [Internationalization (i18n)](https://en.wikipedia.org/wiki/Internationalization_and_localization), elaborated in [Internationalization](internationalization.md);
- [Exceptions](./exceptions.md);
- [Log](./log.md);
- [Queues](./queue.md);
- [Storage](./storage.md);
- [Sessions and Security](./security.md);
- [HTTP Client](./httpclient.md).

Everything else uses most of this.

## Modules
The GNU social [Component-based architecture](https://en.wikipedia.org/wiki/Component-based_software_engineering)
provides a clear distinction between what can not be changed (**[core](http://foldoc.org/core)**), what is replaceable
but must be always present (**[component](http://foldoc.org/component)**), and what can be removed or
added (**[plugin](http://foldoc.org/plugin)**).

This architecture has terminology differences when compared to the one that was [introduced in v2](https://agile.gnusocial.rocks/doku.php?id=v2modules).
In fact, back in v2 - as the term `modules` is not necessarily non-essential - we would keep a "modules" directory near
"plugins", to make the intended difference between both self-evident.

Now in v3, **[Module](http://foldoc.org/module)** is the name we give to the core system managing all the `modules` (as
it is broad enough to include both components and plugins). N.B.: there are not `modules` in the same sense as
there are `components` and `plugins`, the latter descend from the former.

### Components

The most fundamental modules are the components. These are non-core functionality expected to be always available.
Unlike the core, it can be exchanged with equivalent components.

We have components for two key reasons:
- to make available internal higher level APIs, i.e. more abstract ways of interacting with the Core;
- to implement all the basic/essential GNU social functionality in the very same way we would implement plugins. 

Currently, GNU social has the following components:

- Avatar
- Posting

#### Design principles

- Components are independent so do not interfere with each other;
- Component implementations are hidden;
- Communication is through well-defined events and interfaces (for models);
- One component can be replaced by another if its events are maintained.

### Plugins (Unix Tools Design Philosophy)

GNU social is true to the Unix-philosophy of small programs to do a small job.

> * Compact and concise input syntax, making full use of ASCII repertoire to minimise keystrokes;
> * Output format should be simple and easily usable as input for other programs;
> * Programs can be joined together in “pipes” and “scripts” to solve more complex problems;
> * Each tool originally performed a simple single function;
> * Prefer reusing existing tools with minor extension to rewriting a new tool from scratch;
> * The main user-interface software (“shell”) is a normal replaceable program without special privileges;
> * Support for automating routine tasks.
>
> Brian W. Kernighan, Rob Pike: The Unix Programming Environment. Prentice-Hall, 1984.

For instructions on how to implement a plugin and use the core functionality check the [Plugins chapter](./modules.md).

## Dependencies

* The Core only depends on Symfony. We wrote wrappers for all the Symfony functionality we use, making it possible to
  replace Symfony in the future if needed and to make it usable under our
  [programming paradigms, philosophies and conventions](./paradigms.md). V2 tried to do this with PEAR.
* Components only depend on the Core. The Core never depends on Components.
* Components never have inter-dependencies.
* Plugins can depend both on the Core and on Components.
* A plugin may recognize other plugin existence and provide extra functionality via events.

N.B.: "depend on" and "allowing to" have different implications. A plugin can throw an event and other plugins may
handle such event. On the other hand, it's **wrong** if:
* two plugins are inter-dependent in order to provide all of their useful functionality - consider adding [configuration to your plugin](./plugins/configuration.md);
* a component depends on or handles events from plugins - consider throwing an event from your component replacement and
  then handling it from a plugin.

This "hierarchy" makes the flow of things perceivable and predictable, that helps to maintain sanity.