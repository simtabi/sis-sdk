# Installation

Install `simtabi/sis-sdk` with Composer; it is a pure PHP library with no runtime dependencies.

## Requirements

| Requirement | Detail |
|-------------|--------|
| PHP | `^8.5` |
| Runtime dependencies | none |
| Optional extensions | `ext-intl` or `ext-iconv` — alias transliteration only |

The SDK is framework-free: it holds no state, reads no clock, touches no I/O, and pulls in no Composer runtime packages. It runs anywhere PHP 8.5 runs — Laravel, Symfony, a plain script, or a queue worker — without adaptation.

## Install

```bash
composer require simtabi/sis-sdk
```

This brings in the `Simtabi\SIS\` namespace, autoloaded via PSR-4. The built-in SIM profile ships as data in `config/sim.php` inside the package and is loaded automatically, so no publishing or configuration step is required.

## Optional extensions

Alias derivation folds accents so that `Café` and `Cafe` never yield two codes for one client (§5.2). That folding uses, in order of preference:

1. `ext-intl` — `transliterator_transliterate('Any-Latin; Latin-ASCII', …)`.
2. `ext-iconv` — `iconv('UTF-8', 'ASCII//TRANSLIT', …)`.
3. A pass-through fallback when neither extension is present.

Everything else — grammar, check characters, the class register, the lifecycle machine, versions — works identically with or without these extensions. Install one only if your legal names contain accented characters and you want the best-quality transliteration.

## Verify the install

```php
use Simtabi\SIS\Sis;

$sis = new Sis();                            // built-in SIM profile

$sis->validate('SIM-CLT-100001-9O');         // true
echo Sis::SPECIFICATION;                     // 'SIM-STD-0001:2026'
echo Sis::EDITION;                           // 'SIS/1'
```

A `true` from `validate()` on a known-good specimen confirms the profile loaded and the codec compiled.

---

[← Docs index](../README.md#documentation)
