# Changelog

Notable changes to `golded-dev/laravel-ftn-jam`.

This project uses semantic versioning.

## 1.1.0 - 2026-04-29

### Added

- Attach parsed FTN control-line metadata to returned `ParsedMessage` objects.
- Attach message provenance with JAM header path, message number, and header offset.
- Require `golded-dev/laravel-ftn` v1.2.0 in the lockfile.

## 1.0.0 - 2026-04-25

Initial stable release.

### Added

- Add a JAM message-base reader that yields `ParsedMessage` objects.
- Add parsing for common JAM subfields, message bodies, reply links, addresses, timestamps, and message IDs.
- Add a committed JAM fixture so tests run without local archive paths.
