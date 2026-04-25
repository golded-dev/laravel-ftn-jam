# Changelog

## 1.0.0 - 2026-04-25

Initial stable release.

### Added

- Add a JAM message-base reader that yields `ParsedMessage` objects.
- Add parsing for common JAM subfields, message bodies, reply links, addresses, timestamps, and message IDs.
- Add a committed JAM fixture so tests run without local archive paths.
