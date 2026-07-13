# Getting started

A tour of the engine surface — validate, identify, parse, mint, derive aliases, and read versions — first with the built-in SIM profile, then with a custom one.

## The engine

`Simtabi\SIS\Sis` is the entry point and the reference implementation of `SIM-STD-0001:2026`. It implements `Simtabi\SIS\Contract\SisEngine`, and every method is a total function over immutable values: it answers questions and builds commands, but never persists, logs, reads a clock, or dispatches.

```php
use Simtabi\SIS\Sis;

$sis = new Sis();   // built-in SIM profile — byte-identical to the original hardcoded core
```

`new Sis()` is spec-conformant out of the box. The codec, alias policy, and decision dispatcher are compiled once in the constructor and reused across every call.

## Validate

`validate()` returns whether a string is a well-formed, check-valid identifier under the engine's profile.

```php
$sis->validate('SIM-CLT-100001-9O');          // true
$sis->validate('SIM-INV-ADIQ-000001-VY');     // true  (Form S, scoped)
$sis->validate('SIM-CLT-100001-XX');          // false (check characters do not verify)
```

Comparison ignores case and separators, so `'sim clt 100001 9o'` validates too (§2.4).

## Identify

`identify()` returns the `ClassDefinition` for a valid identifier, or `null` when the string is not an identifier of this profile at all.

```php
$class = $sis->identify('SIM-INV-ADIQ-000001-VY');

$class?->code;      // 'INV'
$class?->label();   // 'Invoice'
$class?->isScoped(); // true

$sis->identify('not-an-id');  // null
```

## Parse

`parse()` returns a fully decomposed `Identifier` value object, throwing a `SisException` if the string is malformed, of an unknown class, scope-mismatched, or check-invalid.

```php
$id = $sis->parse('SIM-INV-ADIQ-000001-VY');

$id->class->code;   // 'INV'
$id->scope;         // 'ADIQ'
$id->serial;        // 1
$id->check;         // 'VY'
$id->value;         // 'SIM-INV-ADIQ-000001-VY'
$id->core();        // 'SIM-INV-ADIQ-000001'
$id->isScoped();    // true
```

`Identifier` is a dumb, immutable record — state, ownership, and description live in a register, never in the identifier.

## Mint

`mint()` starts a fluent, immutable `Minter` for a class. Pass the class as a `SimClass` case, a three- or four-letter code, or a `ClassDefinition`. The check characters are always derived, never supplied.

```php
use Simtabi\SIS\Enums\SimClass;

// Build the identifier value directly:
$sis->mint(SimClass::PERSON)->withSerial(100001)->build();       // SIM-PRS-100001-FA
$sis->mint($sis->class('CLT'))->withSerial(100001)->build();     // SIM-CLT-100001-9O

// A scoped (Form S) class needs its client scope:
$sis->mint($sis->class('INV'))
    ->scopedTo('ADIQ')
    ->withSerial(1)
    ->build();                                                   // SIM-INV-ADIQ-000001-VY
```

The serial is supplied by the caller — the core cannot issue one atomically, so your shell (the persistence layer) issues it and hands it in via `withSerial()`. Widths are 6–9 digits (`withWidth()`); widening is always safe, narrowing is forbidden.

`build()` returns the pure `Identifier`. To produce a lifecycle *command* instead — for a registrar to apply — chain `reserve()` or `commission()` after supplying an actor, timestamp, correlation id, and idempotency key:

```php
$reserve = $sis->mint(SimClass::PERSON)
    ->withSerial(100001)
    ->by($actor)
    ->at($now)
    ->correlatedBy($correlationId)
    ->idempotentWith($idempotencyKey)
    ->reserve('new hire');
```

The returned `Reserve` is a command; dispatching it is the registrar's job (see [Architecture](architecture.md)).

## Derive aliases

`aliasCandidates()` ranks human-memorable mnemonic aliases from a legal name (§5.2). The ranking is pure and deterministic; the register decides which candidate is free.

```php
$candidates = $sis->aliasCandidates('AdelsaIQ LLC');

$candidates->all();   // ['ADIQ', 'ADEL', 'ADLS', 'ADAIQ', 'ADELS', …]
```

The policy widens before it mangles: it exhausts every four-character candidate, then five, then six, and only then falls back to a numeric discriminator. `ADIQ` still reads like AdelsaIQ; `ADI2` reads like a database error. To pick the first candidate not already taken, pass the taken set to `choose()`.

## Read versions

`version()` parses a release version — a product alias plus a Semantic Versioning 2.0.0 core (§7.2).

```php
$v = $sis->version('MALISA-1.4.2');

$v->product;        // 'MALISA'
$v->major;          // 1
$v->minor;          // 4
$v->patch;          // 2
$v->isPreRelease(); // false

$v->precedes($sis->version('MALISA-2.0.0'));  // true
```

The product tag shares the mnemonic-alias grammar; build metadata is ignored for ordering, and a pre-release sorts below its release.

## A custom profile

Any organisation adopts SIS with its own issuer, classes, and policies by handing a `SisProfile` to the constructor. The grammar shape, ISO 7064 check, and lifecycle machine stay fixed — only the vocabulary changes.

```php
use Simtabi\SIS\Sis;
use Simtabi\SIS\Profile\SisProfile;

$profile = SisProfile::builder()
    ->issuer('ACME')
    ->class('CUST', label: 'Customer', usesAlias: true, serialStart: 100001)
    ->class('ORD', label: 'Order',    scoped: true)
    ->build();

$sis = new Sis($profile);

$sis->mint($sis->class('CUST'))->withSerial(100001)->build();          // ACME-CUST-100001-LR
$sis->mint($sis->class('ORD'))->scopedTo('WIDG')->withSerial(1)->build(); // ACME-ORD-WIDG-000001-XN
```

Class codes are always three or four letters `A–Z` — the grammar fixes the class token at `[A-Z]{3,4}`. See [Configuration & profiles](configuration.md) for the full profile shape and [The class register](tools/register.md) for defining your own classes.

---

[← Docs index](../README.md#documentation)
