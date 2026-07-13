# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- `SisProfileBuilder::build()` now rejects a serial `defaultWidth` outside the configured
  `[minWidth, maxWidth]` band with a descriptive `InvalidArgumentException`. Previously a
  self-inconsistent profile (e.g. `min_width: 7, default_width: 6`) built without error, then threw a
  misleading `MalformedIdentifierException` on every default-width mint when the codec re-parsed a serial
  padded to a width the grammar band rejects. The failure now surfaces at build time with the real cause.

## [0.1.0] - 2026-07-13

### Added

- Config-driven core: a `SisProfile` (issuer, class register, alias grammar, reserved aliases, serial
  policy) that any organization can supply to adopt SIS with their own vocabulary. The SIM profile
  ships as the built-in default, so zero-config behaviour is spec-conformant.
- An instance `Sis` engine (`Contract\SisEngine`) with decider decoration seams, replacing the former
  static facade.
- The normative specification `SIM-STD-0001:2026`, of which this package is the reference
  implementation, with the class token widened to `[A-Z]{3,4}` (human-readable codes such as `CUST`)
  and the `ENV` environment subtypes (`TST`/`DEV`/`SPT`/`TRN`/`STG`/`PRD`).

### Changed

- Extracted from the `laranail/sis` monorepo into this standalone package, renamed `simtabi/sis` →
  `simtabi/sis-sdk`. Namespace `Simtabi\SIS\` is unchanged.

[Unreleased]: https://github.com/simtabi/sis-sdk/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/simtabi/sis-sdk/releases/tag/v0.1.0
