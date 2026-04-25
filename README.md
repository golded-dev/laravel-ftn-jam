# laravel-ftn-jam

JAM message-base reader for `golded-dev/laravel-ftn`.

This package reads JAM `.JHR` and `.JDT` files and yields `ParsedMessage` objects. It does not write JAM files, repair broken message bases, index areas, or pretend FidoNet history was a tidy little spreadsheet.

## Installation

```bash
composer require golded-dev/laravel-ftn-jam:^1.0
```

PHP 8.4 or newer is required.

## Usage

Pass the JAM base path without an extension:

```php
use Golded\Ftn\Jam\JamReader;

$reader = new JamReader();

foreach ($reader->read('/path/to/message-base/jtest1') as $message) {
    echo $message->subject.PHP_EOL;
}
```

The reader looks for `.jhr` or `.JHR` and `.jdt` or `.JDT` next to the base path. Missing or unreadable files produce an empty result.

## Laravel

There is no service provider, config file, facade, command, migration, or queue integration. This is a plain PHP reader that fits into Laravel projects because the shared FTN package provides the contracts and value objects.

## Development

```bash
composer install
composer test
composer test:types
composer test:refactor
composer test:all
```

The test fixture in `tests/Fixtures/jam` is intentionally committed so the package can test itself in CI.

## Versioning

Versions come from Git tags. Do not add a `version` field to `composer.json`.

## Contributing

Keep the public API small. Add tests with real JAM data when parser behavior changes. If a change needs a fixture, keep it focused and explain why it belongs in the repo.

## Security

Report security issues through GitHub Security Advisories:

https://github.com/golded-dev/laravel-ftn-jam/security/advisories/new

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT. See [LICENSE](LICENSE).
