# Contributing

Thank you for improving the Mosaicora PHP core plugin.

Install dependencies with `composer install`, then run:

```bash
composer validate --strict
composer test
composer package:check
```

Keep changes focused, document public contract changes, and add regression
coverage for every meaningful behavior change. Coordinate cross-repository
changes through released public interfaces rather than unpublished internals.

Report security issues privately as described in [SECURITY.md](SECURITY.md).
