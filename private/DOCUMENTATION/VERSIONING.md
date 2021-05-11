# Versioning

## Release cycle
We follow Semantic Versioning as defined on [https://semver.org/](https://semver.org).

The GNU social platform has a monthly release cycle. That  is, we only increment the version number on day 1 of each month.

For GNU social plugins, we increment version number by commit. Plugins update have a propagation of the same type in the
GNU social's monthly version bump.

If we have various patch type updates in a month, we only bump it by one in the end of the month. If in the same month
we have a minor too, we will only bump the minor value.

# Lifecycle

GNU social has 'dev', 'alpha', 'beta', 'rc' and 'release' cycles. We usually are in `dev` during summer. When we finish a `dev` cycle we release an `alpha` (`dev`s aren't tagged as releases). From `alpha` to `release` it depends on how perfect GNU social is after the end of the `dev` cycle. But we use the number after `alpha` instead of `patch` number for small bug fixes when in this cycle. When we finally go from a `r`elease `c`andidate to a `release`, we party... and we use semver for all the small changes until we start working in another `dev` cycle (usually on another summer).

