<?php

declare(strict_types=1);

namespace Simtabi\SIS\Profile;

/**
 * One class in a register — SIM-STD-0001:2026 §3. A class is a namespace: `SIM-INV-…` can never be
 * confused with `SIM-PRS-…`. The `SimClass` enum names the built-in 22 codes; this record carries the data
 * behind each one, so the code that reads a class does not care whether the vocabulary is SIM's built-in 22
 * classes or a custom profile's.
 *
 * The `code` is the three-letter class token as it appears in the identifier. Class codes are allocated by
 * the specification and NEVER reassigned.
 */
final readonly class ClassDefinition
{
    /**
     * @param  list<string>  $subtypeVocabulary  the controlled subtype vocabulary (§3.7), or empty if the class carries none
     */
    public function __construct(
        public string $code,
        private string $labelText,
        private bool $scoped,
        private bool $aliased,
        private int $firstSerial,
        private array $subtypeVocabulary = [],
    ) {}

    /**
     * Form S (scoped) identifiers belong to a client and carry its alias; Form G (global) identifiers
     * belong to the issuer.
     */
    public function isScoped(): bool
    {
        return $this->scoped;
    }

    /** Whether this class's entities carry a human-facing mnemonic alias (§5). */
    public function usesAlias(): bool
    {
        return $this->aliased;
    }

    /**
     * The first serial for this class. Global serials typically start high so the sequence never advertises
     * how many entities exist; scoped serials start at 1. STD is the deliberate exception (§3.4).
     */
    public function serialStart(): int
    {
        return $this->firstSerial;
    }

    public function label(): string
    {
        return $this->labelText;
    }

    /**
     * The controlled subtype vocabulary for this class (§3.7), or an empty list. A subtype is an ATTRIBUTE
     * in the register, never a segment of the identifier.
     *
     * @return list<string>
     */
    public function subtypes(): array
    {
        return $this->subtypeVocabulary;
    }

    /** Whether this class defines any subtype vocabulary at all. */
    public function hasSubtypeVocabulary(): bool
    {
        return $this->subtypeVocabulary !== [];
    }

    /**
     * Whether $subtype is a permitted subtype for this class. A class with no vocabulary permits NO subtype
     * — its `subtype` column must be null.
     */
    public function permitsSubtype(string $subtype): bool
    {
        return in_array(strtoupper($subtype), $this->subtypeVocabulary, true);
    }
}
