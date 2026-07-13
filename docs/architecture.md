# Architecture

How the engine is composed, where the fixed-versus-configurable boundary is drawn, and how to decorate the surface without touching a call site.

## The composition

`Simtabi\SIS\Sis` is a thin composition root. Its constructor takes an optional `SisProfile` and an optional `DeciderInterface`, and builds four collaborators once:

```
Sis (implements Contract\SisEngine)
├── SisProfile         the register vocabulary — data (issuer, classes, aliases, serials)
├── IdentifierCodec    compiled from the profile: grammar + class register + ISO 7064 check
├── AliasPolicy        alias derivation and reservation, built from the profile's vocabulary
└── DeciderInterface   the decision dispatcher (a Decider fanning out to seven sub-deciders)
```

- The **codec** (`Codec\IdentifierCodec`) is the memoization point. It compiles the profile's `IdentifierGrammar` and `ClassRegister` once, then owns every string-level operation: mint, parse, validate, classify, and building the alias/scope value objects. The value objects it returns are dumb immutable records — all the grammar, class lookup, and check live in the codec so they run once per profile rather than per call.
- The **alias policy** (`Policy\AliasPolicy`) derives and ranks mnemonic aliases and answers whether one is reserved, using the profile's derivation vocabulary, reserved list, and alias grammar.
- The **decider** (`Decider\Decider` by default) is a pure dispatcher: hand it a `Command` and a `Snapshot`, get back a `Decision` — the effects and events to apply. The default fans out to seven sub-deciders (reserve, commission, transition, supersede, release, void, attach-subject).

Everything on the engine is a total function over immutable values. It builds commands and answers questions; it never persists, reads a clock, logs, or dispatches. Issuing a serial atomically and applying a `Decision` are the caller's shell — the register — not the core's job.

## The fixed-versus-configurable boundary

A profile changes the register *vocabulary*. The *rules* are frozen by `SIM-STD-0001:2026` and are not part of any profile.

| Frozen by the spec | Supplied by a profile |
|--------------------|-----------------------|
| Grammar shape: class token `[A-Z]{3,4}`, check `[0-9A-Z]{2}`, and the two Form G / Form S layouts (`Grammar\IdentifierGrammar`) | issuer, separator, serial-width band (within 6–9) |
| ISO 7064 MOD 1271-36 check characters (`Support\CheckCharacters`) | class register: codes, labels, form, alias use, serial starts, subtypes |
| The lifecycle state machine (`Enums\LifecycleState`) | reserved aliases, alias length band, derivation vocabulary |

The grammar compiler quotes only three things out of the profile — the issuer, the scope band (from the alias grammar), and the serial width band — and hardcodes the class and check tokens as private constants. For the SIM profile the compiled patterns are byte-for-byte the original frozen literals.

## Decorability

Two interfaces make the engine open for extension and closed for modification.

`Contract\SisEngine` is the whole engine surface. Coding to it lets a consumer wrap the engine — logging, metrics, multi-tenant profile selection — without depending on the concrete `Sis` class.

`Contract\DeciderInterface` is the pure decision surface: `decide(Command $command, Snapshot $snapshot): Decision`. The default `Decider` implements it, and so can any decorator. Because the engine holds a `DeciderInterface`, you can hand it a wrapped decider without changing a single call site:

```php
use Simtabi\SIS\Contract\Command;
use Simtabi\SIS\Contract\DeciderInterface;
use Simtabi\SIS\Contract\Snapshot;
use Simtabi\SIS\Decision\Decision;
use Simtabi\SIS\Sis;
use Psr\Log\LoggerInterface;

final readonly class LoggingDecider implements DeciderInterface
{
    public function __construct(
        private DeciderInterface $inner,
        private LoggerInterface $log,
    ) {}

    public function decide(Command $command, Snapshot $snapshot): Decision
    {
        $decision = $this->inner->decide($command, $snapshot);
        $this->log->info('sis.decision', ['command' => $command::class]);

        return $decision;
    }
}

// Wrap the engine's default dispatcher, keeping every other call site unchanged:
$sis  = new Sis();
$sis  = $sis->withDecider(new LoggingDecider($sis->decide(...), $log));
```

`withDecider()` returns a copy of the engine that dispatches through the given decider, keeping the same profile. Pass a `DeciderInterface` as the second constructor argument to inject one from the start: `new Sis($profile, $decider)`.

## Why the register is data but the rules are frozen

The core question this design answers: which parts of an identifier system are *policy* (an organisation's choice) and which are *invariants* (properties the whole scheme depends on)?

- **The register is policy.** Which classes exist, what they are called, whether they are scoped, where their serials start — these are business vocabulary. Baking them into code forced every adopter to fork the core; lifting them into a `SisProfile` lets the same total functions serve any issuer's register while `new Sis()` stays byte-identical to the original SIM core.
- **The grammar shape is an invariant.** The class token is a fixed band — three or four uppercase letters, hyphen-delimited so `SIM-INV-…` and `SIM-CUST-…` both parse unambiguously — and the check is always two characters; a check length that varied per profile would break every downstream validator. Fixing the shape at `[A-Z]{3,4}` + `[0-9A-Z]{2}` is what lets any holder of an identifier parse it without the issuer's profile in hand.
- **The check algorithm is an invariant.** MOD 1271-36 was chosen because it catches 100% of single-character substitutions, adjacent and jump transpositions, and twin errors — the errors humans actually make. Letting it be configured would let an adopter silently weaken the one guarantee that protects a mistyped identifier from resolving to the wrong entity. It is never configurable ([details](tools/check-characters.md)).
- **The lifecycle is an invariant.** The single most important rule in the specification — *a commissioned identifier is never released, reused, or reissued* — is enforced structurally: no transition leads back to `Reserved`. A profile that could redraw the state machine could break that guarantee, so the machine lives in an enum, outside every profile.

Architectural rationale lives in this page as prose — there is no separate ADR tree.

---

[← Docs index](../README.md#documentation)
