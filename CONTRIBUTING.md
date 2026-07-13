# Contributing

Thanks for your interest in improving the SIS SDK — the pure-PHP, zero-dependency, config-driven core
of the Simtabi Identifier System.

## Ground rules

- **The specification is authoritative.** `SIM-STD-0001:2026` (`SIM-STD-0001-2026.md`) governs the
  grammar, check characters, class register, and lifecycle. A change to any of those is a specification
  amendment, not a feature.
- **Zero dependencies.** This package requires nothing but PHP (`ext-intl`/`ext-iconv` are optional
  suggests for better transliteration). `BoundaryTest` enforces it at both the manifest and source
  level — no `Illuminate\`, `Laranail\`, or framework imports, ever.
- **Fixed vs configurable.** The identifier grammar shape, the ISO 7064 check characters, and the
  lifecycle state machine are FIXED (interop + safety guarantees). Only the register vocabulary
  (issuer, classes, subtypes, reserved aliases, serial policy) is configurable via `SisProfile`.

## The quality gate

```bash
composer quality      # parallel-lint, Pint, deptrac, PHPStan level 10, PHPUnit
```

Every change must pass the full gate before merge. New behaviour needs tests; the SIM profile is the
conformance oracle — its expected identifier strings stay byte-identical.

## Commits and pull requests

- Subject ≤ 72 chars, imperative mood; body explains *why*.
- Keep each PR focused. Fill in the pull-request template.
- No AI/assistant attribution in commits or PRs.

## Reporting security issues

Do not open a public issue. Follow [SECURITY.md](SECURITY.md).
