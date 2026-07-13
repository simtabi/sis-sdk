# Aliases

Human-memorable mnemonic codes derived from a legal name — `ADIQ` for AdelsaIQ, not `ADI2`.

## The alias grammar

`Profile\AliasGrammar` is the single source of the alias shape (§5.1). The first character is always a letter `[A-Z]` and the body is always `[A-Z0-9]`; only the length band is configurable per profile. SIM uses four to six characters, which compiles to the frozen `[A-Z][A-Z0-9]{3,5}`.

```php
use Simtabi\SIS\Sis;

$sis = new Sis();

$alias = $sis->alias('ADIQ');   // Alias value object, or MalformedAliasException
$alias->value;                  // 'ADIQ'

$sis->alias('adiq')->value;     // 'ADIQ'  — trimmed and uppercased
$sis->alias('1BAD');            // throws — must start with a letter
```

The same grammar governs the Form S scope token, the alias-derivation output, and the product tag of a release version — each reads its shape from `AliasGrammar` rather than repeating the literal.

## `AliasPolicy` — derivation and reservation

`Policy\AliasPolicy` derives ranked aliases from a legal name and answers whether an alias is reserved. Its vocabulary — legal suffixes, generic words, reserved list, length band — is profile data injected at construction, so the ranking is identical for SIM and any custom profile. `candidates()` is pure and deterministic; the ranking never leaves the core.

```php
$sis->aliasCandidates('AdelsaIQ LLC')->all();  // ['ADIQ', 'ADEL', 'ADLS', 'ADAIQ', …]

$sis->isReservedAlias('TEST');   // true  — in the SIM reserved list
$sis->isReservedAlias('ADIQ');   // false
```

The SIM reserved list is `SIMT, PROS, TEST, NULL, VOID, TEMP, DEMO, NONE, ADMIN, ROOT, SYST`. Reserved aliases (and any that fail the grammar) are filtered out of the candidate list.

## Widen before mangle

Four letters give 456,976 combinations, so the space was never the constraint — the scarce resource is *derivable, memorable* codes. The policy therefore widens before it mangles:

1. Exhaust every four-character candidate.
2. Then every five-character candidate.
3. Then every six-character candidate.
4. Only then fall back to a numeric discriminator (`ADI2`, `ADI3`, …), the last resort.

`ACMX` still reads like Acme; `ACME2` reads like a database error. Within each length the policy tries several strategies in rank order: head plus distinctive tail (this is what turns *AdelsaIQ* into `ADIQ` rather than the flatter `ADEL`), truncation, initials, the consonant skeleton (dropping vowels), a re-admitted generic word to break a tie, then first-two plus last-two.

Before deriving, the name is normalised: accents are folded (so `Café` and `Cafe` never yield two codes), `&` becomes `AND`, legal-form suffixes (`LLC`, `INC`, …) are stripped from the tail, and generic words (`GROUP`, `LABS`, …) are set aside and re-admitted only to break a tie.

## Choosing a free candidate

The ranking is deterministic; which candidate is *free* is a register question the shell answers. `AliasCandidates::choose()` picks the first candidate not in the taken set, or throws `ExhaustedAliasSpaceException`.

```php
use Simtabi\SIS\Identifier\TakenAliases;

$candidates = $sis->aliasCandidates('AdelsaIQ LLC');

$candidates->all();       // full ranked list, best first
$candidates->isEmpty();   // false

$alias = $candidates->choose(new TakenAliases(['ADIQ']));  // skips the taken ADIQ → ADEL
```

A chosen alias becomes a client's scope token in Form S identifiers (`SIM-INV-ADIQ-000001-VY`) or an entity's mnemonic for alias-bearing classes such as `CLT`, `PRD`, and `SVC`.

---

[← Docs index](../../README.md#documentation)
