# simtabi/sis-sdk

[![Packagist Version](https://img.shields.io/packagist/v/simtabi/sis-sdk.svg?style=flat-square)](https://packagist.org/packages/simtabi/sis-sdk)
[![Tests](https://img.shields.io/github/actions/workflow/status/simtabi/sis-sdk/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/simtabi/sis-sdk/actions)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/simtabi/sis-sdk/static-analysis.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/simtabi/sis-sdk/actions)
[![License MIT](https://img.shields.io/packagist/l/simtabi/sis-sdk.svg?style=flat-square)](LICENSE)

> The pure, framework-free, config-driven core of the Simtabi Identifier System — grammar, ISO 7064 check characters, a data-driven class register, the lifecycle state machine, alias derivation, and semver releases. The reference implementation of `SIM-STD-0001:2026`.

Requires PHP `^8.5`. Zero runtime dependencies (`ext-intl` / `ext-iconv` are optional, for best-quality alias transliteration only). Any organization can adopt SIS with their own issuer, classes, and policies via a `SisProfile`; the SIM profile ships as the built-in default, so zero-config behaviour is spec-conformant.

## Install

```bash
composer require simtabi/sis-sdk
```

## Quick start

```php
use Simtabi\SIS\Sis;

$sis = new Sis();                        // SIM profile — spec-conformant out of the box

$sis->validate('SIM-CLT-100001-9O');     // true
$sis->identify('SIM-INV-ADIQ-000001-VY'); // the Invoice class definition
$identifier = $sis->mint($sis->class('CLT'))->withSerial(100001)->build();
```

A company adopts SIS with their own vocabulary by supplying a profile:

```php
use Simtabi\SIS\Sis;
use Simtabi\SIS\Profile\SisProfile;

$profile = SisProfile::builder()
    ->issuer('ACME')
    ->class('CUST', label: 'Customer', scoped: false, usesAlias: true, serialStart: 100001)
    ->class('ORD',  label: 'Order',    scoped: true,  serialStart: 1)
    ->build();                                // class codes are three or four letters A–Z

$sis = new Sis($profile);                 // grammar, check characters, and lifecycle stay fixed
```

## <a name="documentation"></a>Documentation

Hosted at **<https://opensource.simtabi.com/documentation/simtabi/sis-sdk/>**.

### Guides
- [Installation](docs/installation.md), [Getting started](docs/getting-started.md), [Configuration & profiles](docs/configuration.md), [Architecture](docs/architecture.md)

### Reference
- [The class register](docs/tools/register.md) · [Check characters](docs/tools/check-characters.md) · [Aliases](docs/tools/aliases.md) · [Profiles](docs/tools/profiles.md)

## Stability

Pre-1.0. While pre-stable, a single moving `v0.1.0` tag tracks the latest state; constraints resolve `^0.1`.

## License

MIT. Copyright (c) 2026 Simtabi LLC. See [LICENSE](LICENSE).
