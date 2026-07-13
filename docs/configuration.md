# Configuration & profiles

A `SisProfile` is the complete register vocabulary the engine is built from — everything the SIM core once hardcoded, now expressed as data.

## What a profile is

`Simtabi\SIS\Profile\SisProfile` is an immutable record carrying the issuer, separator, class register, alias grammar, reserved aliases, serial rules, capacity threshold, alias-derivation vocabulary, and the spec/edition tags. `new Sis($profile)` runs the same total functions over whatever vocabulary the profile declares. Omit it — `new Sis()` — and the built-in SIM profile is used, making zero-config behaviour byte-identical to the original core.

There are four ways to obtain a profile:

| Constructor | Use |
|-------------|-----|
| `SisProfile::sim()` | the built-in SIM profile (same as `new Sis()`) |
| `SisProfile::builder()` | fluent, validating construction in code |
| `SisProfile::fromArray($data)` | build from a plain PHP array |
| `SisProfile::fromFile($path)` | build from a PHP file that returns an array |

## The builder

`SisProfile::builder()` returns a `SisProfileBuilder`. Defaults are the SIM defaults, so a profile is a small set of overrides — an issuer and a handful of `class()` calls is enough for a working register.

```php
use Simtabi\SIS\Profile\SisProfile;

$profile = SisProfile::builder()
    ->issuer('ACME')                                        // required, non-empty
    ->separator('-')                                        // default '-'
    ->class('CUST', label: 'Customer', usesAlias: true, serialStart: 100001)
    ->class('ORD', label: 'Order', scoped: true)
    ->capacityThreshold(0.80)
    ->build();
```

`build()` enforces the invariants the grammar depends on: a non-empty issuer, class codes of three or four letters `A–Z`, no duplicate codes, and a serial-width band inside the frozen 6–9 digits. A violation throws `InvalidArgumentException`.

The `class()` method signature:

```php
public function class(
    SimClass|string $code,     // three- or four-letter code, or a SimClass case
    string $label = '',        // human label; defaults to the code
    bool $scoped = false,      // Form S (carries a client scope) vs Form G
    bool $usesAlias = false,   // does this class's entities carry a mnemonic alias?
    ?int $serialStart = null,  // defaults to the form's global/scoped start
    array $subtypes = [],      // controlled subtype vocabulary (attributes, not segments)
): self
```

## The array and file shape

`fromArray()` and `fromFile()` read the same keyed structure. Only the profile keys below are read; anything else is left at its default.

| Key | Type | Meaning |
|-----|------|---------|
| `issuer` | string | the issuer token, e.g. `SIM` |
| `separator` | string | segment separator, default `-` |
| `classes` | list of maps | one entry per class (see below) |
| `aliases.grammar` | `{min, max}` | mnemonic-alias length band, default 4–6 |
| `aliases.reserved` | list of strings | aliases no entity may use |
| `aliases.derivation` | map | derivation vocabulary (see below) |
| `serials` | map | serial-space rules (see below) |
| `capacity_threshold` | number | fraction of serial space at which the register warns, default `0.80` |
| `spec` | string | controlling spec document, default `SIM-STD-0001:2026` |
| `edition` | string | spec edition, default `SIS/1` |

Each `classes[]` entry:

| Field | Type | Default |
|-------|------|---------|
| `code` | string (3 or 4 letters) | required |
| `label` | string | the code |
| `scoped` | bool | `false` |
| `uses_alias` | bool | `false` |
| `serial_start` | int | the form's global/scoped start |
| `subtypes` | list of strings | `[]` |

Each `serials` field maps to `SerialRules`:

| Field | Default | Meaning |
|-------|---------|---------|
| `global_start` | `100001` | first serial for Form G classes |
| `scoped_start` | `1` | first serial for Form S classes |
| `min_width` | `6` | narrowest render width (floor 6) |
| `max_width` | `9` | widest render width (ceiling 9) |
| `default_width` | `6` | width used when none is given |

Each `aliases.derivation` field maps to `AliasDerivation`:

| Field | Meaning |
|-------|---------|
| `legal_suffixes` | company-form suffixes stripped from the tail (`LLC`, `INC`, …) |
| `generic_words` | words re-admitted only to break a tie (`GROUP`, `LABS`, …) |
| `padding` | character that pads a code short of the minimum, default `X` |
| `vowels` | letters the consonant-skeleton derivation may drop |
| `min`, `max` | derivation length band, default 4–6 |

```php
$profile = SisProfile::fromArray([
    'issuer' => 'ACME',
    'classes' => [
        ['code' => 'CUST', 'label' => 'Customer', 'uses_alias' => true, 'serial_start' => 100001],
        ['code' => 'ORD', 'label' => 'Order', 'scoped' => true],
    ],
    'aliases' => [
        'grammar'  => ['min' => 4, 'max' => 6],
        'reserved' => ['ADMIN', 'ROOT', 'TEST'],
    ],
]);
```

`fromFile($path)` simply `require`s a PHP file that returns such an array and passes it to `fromArray()`. If the file returns a non-array it throws `InvalidArgumentException`.

## The `config/sim.php` reference

The built-in SIM profile is not hardcoded in a class — it ships as the data file `config/sim.php` at the package root, and `SisProfile::sim()` loads it through `fromFile()`. Editing the SIM vocabulary means editing that file, not any PHP class. Its shape is exactly the array shape above:

```php
return [
    'issuer' => 'SIM',
    'separator' => '-',
    'serials' => [
        'global_start' => 100001, 'scoped_start' => 1,
        'min_width' => 6, 'max_width' => 9, 'default_width' => 6,
    ],
    'aliases' => [
        'grammar'  => ['min' => 4, 'max' => 6],
        'reserved' => ['SIMT', 'PROS', 'TEST', 'NULL', 'VOID', 'TEMP', 'DEMO', 'NONE', 'ADMIN', 'ROOT', 'SYST'],
        'derivation' => [ /* legal_suffixes, generic_words, padding, vowels, min, max */ ],
    ],
    'capacity_threshold' => 0.80,
    'spec' => Spec::DOCUMENT, 'edition' => Spec::EDITION,
    'classes' => [ /* the 22 SIM classes */ ],
];
```

The file is runtime data, not a dev-only file: it ships in the dist tarball and is never export-ignored. The 22 SIM classes it declares are catalogued in [The class register](tools/register.md).

## Fixed vs configurable

A profile changes the *vocabulary*, never the *rules*. Three things are fixed by the specification and are NOT part of any profile:

| Fixed by the spec | Configurable by a profile |
|-------------------|---------------------------|
| Identifier grammar shape — class token `[A-Z]{3,4}`, check `[0-9A-Z]{2}`, the two Form G / Form S layouts | issuer, separator, and the serial-width band (within 6–9) |
| ISO 7064 MOD 1271-36 check characters ([details](tools/check-characters.md)) | class codes, labels, form, alias use, serial starts, subtypes |
| The lifecycle state machine (Reserved → Commissioned → …) | reserved aliases and the alias length band (4–6 by default) |
| The alias grammar's fixed prefix rule (`[A-Z]` then `[A-Z0-9]`) | the alias-derivation vocabulary (suffixes, generic words, vowels, padding) |

The builder actively guards the fixed boundary: it rejects a class code that is neither three nor four letters and a serial band outside 6–9.

## A full custom-company example

```php
use Simtabi\SIS\Sis;
use Simtabi\SIS\Profile\SisProfile;

$profile = SisProfile::builder()
    ->issuer('ACME')
    ->class('CUST', label: 'Customer', usesAlias: true, serialStart: 100001)
    ->class('ORD', label: 'Order',    scoped: true)
    ->class('INV', label: 'Invoice',  scoped: true)
    ->class('PRD', label: 'Product',  usesAlias: true)
    ->reservedAliases(['ADMIN', 'ROOT', 'TEST'])
    ->capacityThreshold(0.80)
    ->build();

$sis = new Sis($profile);

$sis->mint($sis->class('CUST'))->withSerial(100001)->build();              // ACME-CUST-100001-LR
$sis->mint($sis->class('ORD'))->scopedTo('WIDG')->withSerial(1)->build(); // ACME-ORD-WIDG-000001-XN
$sis->mint($sis->class('INV'))->scopedTo('WIDG')->withSerial(42)->build(); // ACME-INV-WIDG-000042-7S
$sis->mint($sis->class('PRD'))->withSerial(100001)->build();              // ACME-PRD-100001-HT

$sis->aliasCandidates('Widgets Inc')->all();  // ['WINC', 'WIDG', 'WIID', 'WDGT', …]
```

The same engine, the same check algorithm, the same lifecycle — a different register.

---

[← Docs index](../README.md#documentation)
