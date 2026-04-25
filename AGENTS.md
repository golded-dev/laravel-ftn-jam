# Agent Instructions

This package reads FTN JAM message bases for `golded-dev/laravel-ftn`.

## Boundaries

- `Golded\Ftn\Jam\JamReader` is the public reader.
- Keep this package focused on reading JAM files.
- Do not add writers, scanners, area discovery, repair tools, or Laravel service providers unless the user asks for that work directly.
- Shared contracts and value objects belong in `golded-dev/laravel-ftn`.

## Parser rules

- Treat JAM as byte-level input. Small changes can break real archives.
- Prefer explicit parsing over clever abstractions.
- Keep missing or unreadable files as empty reads unless the public contract changes deliberately.
- Preserve case-insensitive extension lookup for JAM files.
- Use committed fixtures for behavior that depends on real message-base bytes.

## Tests and quality

Run the full gate before release work:

```bash
composer test:all
```

Useful smaller checks:

```bash
composer test
composer test:types
composer test:refactor
composer validate --strict
git diff --check
```

## Dependencies

- Keep PHP at the version range declared in `composer.json`.
- Do not add a local path repository to `composer.json`.
- Do not add a `version` field. Composer versions come from Git tags.
- Keep `golded-dev/laravel-ftn` on a stable constraint for public releases.

## Fixtures

`tests/Fixtures/jam` is intentionally committed. Do not point tests at sibling repos or local archives. CI cannot read your hard drive, no matter how persuasive the path looks.

## Docs

Keep docs direct and concrete. Explain what this reads, what it does not read, how to install it, and how to test it. Avoid pitch-deck words and imaginary Laravel features.
