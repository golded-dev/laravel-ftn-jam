# Contributing

Keep changes small and boring in the useful sense.

## Setup

```bash
composer install
composer test:all
```

## Pull requests

- Keep parser changes covered by tests.
- Use real JAM fixtures when byte-level behavior matters.
- Do not add Laravel integration unless the package actually needs it.
- Do not widen supported PHP versions without running the full quality gate on those versions.

## Release notes

Update `CHANGELOG.md` for user-visible changes. Composer package versions come from Git tags, not from a `version` field in `composer.json`.
