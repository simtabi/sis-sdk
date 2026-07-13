# Profiles

`SisProfile` is the register vocabulary the engine runs on — the built-in SIM profile by default, or any organisation's own.

## `SisProfile` and `SimProfile`

`Profile\SisProfile` is an immutable record carrying everything the SIM core once hardcoded: the issuer, separator, class register, alias grammar, reserved aliases, serial rules, capacity threshold, alias-derivation vocabulary, and the spec/edition tags. It is what `new Sis($profile)` runs on.

`Profile\Sim\SimProfile` is the factory for the built-in SIM profile. It holds no data of its own — it loads `config/sim.php` through `SisProfile::fromFile()`, so the SIM vocabulary lives in one data file, and `new Sis()` is byte-identical to the original hardcoded core.

```php
use Simtabi\SIS\Profile\Sim\SimProfile;
use Simtabi\SIS\Profile\SisProfile;

$profile = SimProfile::create();   // the built-in SIM profile
$profile = SisProfile::sim();      // identical — a convenience alias

$profile->issuer();      // 'SIM'
$profile->separator();   // '-'
$profile->classes();     // ClassRegister of the 22 SIM classes
$profile->serials();     // SerialRules(globalStart: 100001, scopedStart: 1, …)
$profile->spec();        // 'SIM-STD-0001:2026'
$profile->edition();     // 'SIS/1'
```

Accessors: `issuer()`, `separator()`, `classes()`, `aliasGrammar()`, `reservedAliases()`, `serials()`, `capacityThreshold()`, `aliasDerivation()`, `spec()`, `edition()`.

## Building, loading, overriding

Four entry points construct a profile:

| Constructor | Use |
|-------------|-----|
| `SisProfile::sim()` | the built-in SIM profile |
| `SisProfile::builder()` | fluent, validating construction in code |
| `SisProfile::fromArray($data)` | build from a plain PHP array |
| `SisProfile::fromFile($path)` | `require` a PHP file that returns such an array |

Because builder defaults are the SIM defaults, a custom profile is a small set of overrides — an issuer and a handful of `class()` calls is a working register. The full array/file shape (issuer, separator, `classes`, `aliases`, `serials`, `capacity_threshold`, `spec`, `edition`) is documented in [Configuration & profiles](../configuration.md).

```php
use Simtabi\SIS\Profile\SisProfile;

// From code:
$profile = SisProfile::builder()
    ->issuer('ACME')
    ->class('CUST', label: 'Customer', usesAlias: true, serialStart: 100001)
    ->build();

// From an array:
$profile = SisProfile::fromArray([
    'issuer'  => 'ACME',
    'classes' => [['code' => 'CUST', 'label' => 'Customer', 'uses_alias' => true]],
]);

// From a file that returns the same array (mirrors how config/sim.php is loaded):
$profile = SisProfile::fromFile(__DIR__ . '/acme-profile.php');
```

`build()` validates the fixed boundary the grammar depends on: a non-empty issuer, class codes of three or four letters `A–Z`, no duplicate codes, and a serial-width band inside 6–9. What a profile can and cannot change is set out in [Configuration & profiles](../configuration.md#fixed-vs-configurable).

## The `Environment` enum

`Enums\Environment` models six deployment environments as canonical three-letter codes. These are subtype codes (the `ENV` class's vocabulary), which stay three letters, while common real-world spellings (`TEST`, `PROD`, `production`) are accepted case-insensitively and normalised back.

| Case | Code | Label | Accepted aliases |
|------|------|-------|------------------|
| `Test` | `TST` | Test | `TST`, `TEST`, `test` |
| `Development` | `DEV` | Development | `DEV`, `development` |
| `Support` | `SPT` | Support | `SPT`, `support` |
| `Training` | `TRN` | Training | `TRN`, `training` |
| `Staging` | `STG` | Staging | `STG`, `staging` |
| `Production` | `PRD` | Production | `PRD`, `PROD`, `production` |

```php
use Simtabi\SIS\Enums\Environment;

Environment::codes();                       // ['TST', 'DEV', 'SPT', 'TRN', 'STG', 'PRD']
Environment::tryFromAlias('production');    // Environment::Production, or null
Environment::fromAlias('PROD');             // Environment::Production, or throws ValueError
Environment::Staging->label();              // 'Staging'
```

`tryFromAlias()` returns `null` on no match; `fromAlias()` throws `ValueError`.

## The `ENV` class uses `Environment::codes()`

The six environment codes are the natural subtype vocabulary for the SIM `ENV` class. In `config/sim.php` the `ENV` class declares its subtypes directly from the enum:

```php
['code' => 'ENV', 'label' => 'Environment', 'scoped' => true, 'subtypes' => Environment::codes()],
```

So an `ENV` entity's subtype is one of `TST, DEV, SPT, TRN, STG, PRD` — an attribute in the register, never a segment of the identifier. A custom profile can feed the same `Environment::codes()` to its own environment class, or declare a different subtype set entirely.

---

[← Docs index](../../README.md#documentation)
