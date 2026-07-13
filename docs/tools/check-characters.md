# Check characters

Two trailing characters — ISO 7064 MOD 1271-36 — that catch the errors humans actually make when they mistype an identifier.

## The two-character check

Every identifier ends in a two-character check computed over its core (everything before the final separator). `Support\CheckCharacters` is the pure implementation (§4):

```php
use Simtabi\SIS\Support\CheckCharacters;

CheckCharacters::for('SIM-CLT-100001');        // '9O'
CheckCharacters::verify('SIM-CLT-100001', '9O'); // true
CheckCharacters::verify('SIM-CLT-100001', 'XX'); // false
```

You rarely call this directly: the codec derives the check on `mint()` and verifies it on `parse()`/`validate()`. The check characters are always derived, never supplied by a caller.

## The algorithm

MOD 1271-36 over the alphabet `0-9A-Z` (radix 36, modulus 1271):

1. **Normalise.** Strip everything but `A–Z0–9` and uppercase — separators and case are irrelevant to the check, only the payload matters (§2.4). So `SIM-CLT-100001` and `simclt100001` produce the same check.
2. **Accumulate.** For each character value `v` (its index in the alphabet), fold `p = ((p + v) * 36) mod 1271`, starting from `p = 0`.
3. **Second shift.** Apply one more `p = (p * 36) mod 1271`. This second shift is what makes it a *two*-character check rather than one.
4. **Complement.** Compute `check = (1271 + 1 - p) mod 1271`, then render it as two base-36 digits: `ALPHABET[check div 36]` followed by `ALPHABET[check mod 36]`.

An illegal character in the payload throws `IllegalCharacterException`. `verify()` compares in constant time with `hash_equals()`, so a check comparison never leaks timing information.

## Why it is fixed and never configurable

The check algorithm is frozen by the specification — it is not part of any `SisProfile`. Two reasons:

- **It is the one guarantee that survives a typo.** MOD 1271-36 was measured to catch 100% of single-character substitutions, adjacent transpositions, jump transpositions, and twin errors — the mistakes people make transcribing an identifier. Letting an adopter reconfigure it would let them silently weaken the protection that keeps a mistyped identifier from resolving to a *different* valid entity.
- **The weaker alternatives were measured and rejected (§4.2).** The single-character MOD 37,36 fails on adjacent transpositions and gives `SIM-PRS-100001` and `SIM-PRS-100010` the same check character. MOD 97-10 (the IBAN check) misses 27 of 5,320 substitutions. Those rejections are proven by a property-based test in the suite; the rejected algorithms are not to be re-proposed.

Because the algorithm is universal, any holder of an identifier can verify it without the issuer's profile — only the class register and grammar band vary per profile, never the check.

## Verifying an identifier

The whole check is folded into ordinary validation, so most callers never touch `CheckCharacters` at all:

```php
use Simtabi\SIS\Sis;

$sis = new Sis();

$sis->validate('SIM-CLT-100001-9O');   // true  — check verifies
$sis->validate('SIM-CLT-100001-9P');   // false — check does not verify
```

A check-character mismatch surfaces on `parse()` as `CheckCharacterMismatchException`, carrying both the expected and the supplied check.

---

[← Docs index](../../README.md#documentation)
