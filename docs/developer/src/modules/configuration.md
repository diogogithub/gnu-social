# Adding configuration to a Module

## The trade-off between re-usability and usability

> The more general the interface, the greater the re-usability, but it is then more
> complex and hence less usable.

It is often good to find a compromise by means of configuration.

## Module configuration

The default configuration is placed in `local/plugins/Name/config.yaml`.

```yaml
parameters:
  name:
    setting: 42
```

A user can override this configuration in `social.local.yaml` with:

```yaml
parameters:
  locals:
    name:
      setting: 1337
```

Note that if plugin's name is something like `FirstSecond`, it will become `first_second`
in the configuration file.