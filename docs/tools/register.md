# The class register

Twenty-two built-in classes, each a namespace in the identifier: `SIM-INV-…` can never be confused with `SIM-PRS-…`.

## `ClassDefinition` and `ClassRegister`

A class is the second segment of every identifier — the three- or four-letter token that says what kind of thing this is (§3). Two types model it:

- `Profile\ClassDefinition` — one class as data: its `code`, label, form (scoped or global), whether it carries a mnemonic alias, its first serial, and its controlled subtype vocabulary. Class codes are allocated by the specification and never reassigned.
- `Profile\ClassRegister` — the class vocabulary of a profile, keyed by three- or four-letter code. This is the data-driven register behind the class enum: the SIM profile carries the built-in 22, a custom profile carries whatever its builder declared.

```php
use Simtabi\SIS\Sis;

$sis = new Sis();

$sis->classes();                    // the ClassRegister
$sis->classes()->codes();           // ['CLT', 'PRS', 'VND', … 22 codes]
$sis->classes()->has('INV');        // true
$sis->classes()->tryClass('ZZZ');   // null (unknown code, no throw)

$inv = $sis->class('INV');          // ClassDefinition, or UnknownIdClassException
$inv->code;                 // 'INV'
$inv->label();              // 'Invoice'
$inv->isScoped();           // true  (Form S)
$inv->usesAlias();          // false
$inv->serialStart();        // 1
$inv->subtypes();           // []
```

`ClassDefinition` methods:

| Method | Returns |
|--------|---------|
| `->code` | the three- or four-letter token (public property) |
| `label()` | the human label |
| `isScoped()` | Form S (belongs to a client, carries a scope) vs Form G |
| `usesAlias()` | whether entities of this class carry a mnemonic alias (§5) |
| `serialStart()` | the first serial for this class |
| `subtypes()` | the controlled subtype vocabulary, or `[]` |
| `hasSubtypeVocabulary()` | whether any subtype vocabulary is defined |
| `permitsSubtype($s)` | whether `$s` is a permitted subtype |

## The `SimClass` enum

`Enums\SimClass` names the built-in 22 codes as a backed enum, so consumers get a compile-time-checked, greppable handle instead of a bare `'CLT'` literal. Each case is backed by exactly its three- or four-letter token — `SimClass::CLIENT->value === 'CLT'`.

```php
use Simtabi\SIS\Enums\SimClass;

$sis->class(SimClass::CLIENT);              // resolve a definition from a case
$sis->mint(SimClass::PERSON)->withSerial(100001)->build();
```

The register itself is data-driven (loaded from `config/sim.php`), so the enum is a convenience handle over the register, not its source. A custom profile is not required to use these codes.

## Scoped vs global — Form G and Form S

A class is one of two forms, which determines the identifier layout (§2):

```
Form G (global):  {ISSUER}-{CLASS}-{SERIAL}-{CHECK}          SIM-PRS-100001-FA
Form S (scoped):  {ISSUER}-{CLASS}-{SCOPE}-{SERIAL}-{CHECK}  SIM-INV-ADIQ-000001-VY
```

- **Form G (global)** identifiers belong to the issuer. Their serials typically start high (100001) so the sequence never advertises how many entities exist.
- **Form S (scoped)** identifiers belong to a client and carry that client's alias as a scope segment. Their serials start at 1.

`STD` (Standard) is the deliberate exception: a global class whose serials start at 1 (§3.4).

## The 22 SIM classes

Grouped as in `config/sim.php`. Form is G unless marked S; serial start follows the form unless overridden.

| Code | `SimClass` case | Label | Form | Alias | Serial start | Subtypes |
|------|-----------------|-------|:----:|:-----:|-------------:|----------|
| `CLT` | `CLIENT` | Client | G | ✓ | 100001 | — |
| `PRS` | `PERSON` | Person | G | | 100001 | ENG, DES, PM, OPS, BIZ, EXE |
| `VND` | `VENDOR` | Vendor | G | | 100001 | — |
| `DPT` | `DEPARTMENT` | Department | G | ✓ | 100001 | ENG, DES, OPS, BIZ, FIN, EXE |
| `PRJ` | `PROJECT` | Project | S | | 1 | — |
| `SOW` | `SOW` | Statement of Work | S | | 1 | — |
| `CHG` | `CHANGE_ORDER` | Change Order | S | | 1 | — |
| `MIL` | `MILESTONE` | Milestone | S | | 1 | — |
| `QUO` | `QUOTE` | Quote | S | | 1 | — |
| `INV` | `INVOICE` | Invoice | S | | 1 | — |
| `CRN` | `CREDIT_NOTE` | Credit Note | S | | 1 | — |
| `PRD` | `PRODUCT` | Product | G | ✓ | 100001 | — |
| `SVC` | `SERVICE` | Service | G | ✓ | 100001 | — |
| `CMP` | `COMPONENT` | Component | G | ✓ | 100001 | — |
| `REL` | `RELEASE` | Release | G | | 100001 | — |
| `AST` | `ASSET` | Asset | G | | 100001 | LAP, MON, PHN, SRV, LIC, DOM, KEY, MSC |
| `DOC` | `DOCUMENT` | Document | S | | 1 | ICA, MSA, SOW, NDA, CHG, DPA, IPA, EMP, QUO |
| `STD` | `STANDARD` | Standard | G | | 1 | — |
| `ADR` | `DECISION` | Decision Record | G | | 100001 | — |
| `TKT` | `TICKET` | Ticket | S | | 1 | — |
| `INC` | `INCIDENT` | Incident | G | | 100001 | — |
| `ENV` | `ENVIRONMENT` | Environment | S | | 1 | TST, DEV, SPT, TRN, STG, PRD |

The `ENV` subtypes are the six deployment environments from `Enums\Environment::codes()` — see [Profiles](profiles.md).

## Subtypes are attributes, not segments

A subtype is a controlled vocabulary the register may attach to an entity as an ATTRIBUTE — it is never a segment of the identifier (§3.7). A class with no vocabulary permits no subtype; its subtype column must be null.

```php
$sis->class('PRS')->hasSubtypeVocabulary();   // true
$sis->class('PRS')->permitsSubtype('ENG');    // true
$sis->class('PRS')->permitsSubtype('ZZZ');    // false
$sis->class('CLT')->hasSubtypeVocabulary();   // false — CLT permits no subtype
```

## Defining your own classes

A custom profile declares its own register. Each class code must be three or four letters `A–Z` and unique within the profile; the builder rejects anything else.

```php
use Simtabi\SIS\Profile\SisProfile;

$profile = SisProfile::builder()
    ->issuer('ACME')
    ->class('CUST', label: 'Customer', usesAlias: true, serialStart: 100001)
    ->class('ORD', label: 'Order', scoped: true)                       // Form S, serials from 1
    ->class('AST', label: 'Asset', subtypes: ['LAP', 'MON', 'SRV'])    // subtype vocabulary
    ->build();
```

`serialStart` defaults to the profile's global start for a Form G class and scoped start for a Form S class; pass it only to override (as `STD` does). See [Configuration & profiles](../configuration.md) for the full array/file shape.

---

[← Docs index](../../README.md#documentation)
