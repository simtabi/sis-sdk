<?php

declare(strict_types=1);

namespace Simtabi\SIS\Profile;

/**
 * The shape of a mnemonic alias — SIM-STD-0001:2026 §5.1. The first character is always a letter `[A-Z]`
 * and the body is always `[A-Z0-9]`; only the length band is configurable per profile. SIM uses four to
 * six characters, which compiles to the frozen `[A-Z][A-Z0-9]{3,5}`.
 *
 * This is the single source of the alias/scope/product shape: `Alias`, the Form S scope token, the alias
 * derivation policy, and the release-version product tag all read their grammar from here rather than
 * repeating the literal.
 */
final readonly class AliasGrammar
{
    public function __construct(
        public int $min = 4,
        public int $max = 6,
    ) {}

    /** The un-anchored, un-delimited body fragment, e.g. `[A-Z][A-Z0-9]{3,5}`, for embedding in a larger pattern. */
    public function fragment(): string
    {
        return '[A-Z][A-Z0-9]{' . ($this->min - 1) . ',' . ($this->max - 1) . '}';
    }

    /** The full anchored, delimited pattern, e.g. `/^[A-Z][A-Z0-9]{3,5}$/`. */
    public function pattern(): string
    {
        return '/^' . $this->fragment() . '$/';
    }

    public function matches(string $value): bool
    {
        return preg_match($this->pattern(), $value) === 1;
    }
}
