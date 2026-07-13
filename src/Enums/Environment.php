<?php

declare(strict_types=1);

namespace Simtabi\SIS\Enums;

use ValueError;

/**
 * The supported deployment environments, as canonical three-letter codes.
 *
 * These are subtype codes (the SIM `ENV` class's vocabulary), which stay three letters. Real-world spellings
 * vary, though — `TEST`, `PROD`, `production` — so each case carries
 * a set of case-insensitive {@see self::aliases()} that {@see self::tryFromAlias()} and {@see self::fromAlias()}
 * accept and normalise back to the canonical value.
 *
 * These are the natural subtype vocabulary for the SIM `ENV` class; {@see self::codes()} feeds that class's
 * subtypes in the built-in SIM profile.
 */
enum Environment: string
{
    /** Automated / exploratory testing. */
    case Test = 'TST';

    /** Active development. */
    case Development = 'DEV';

    /** Customer / production support. */
    case Support = 'SPT';

    /** User and staff training. */
    case Training = 'TRN';

    /** Pre-production staging. */
    case Staging = 'STG';

    /** Live production. */
    case Production = 'PRD';

    /** The human-facing name of the environment. */
    public function label(): string
    {
        return match ($this) {
            self::Test => 'Test',
            self::Development => 'Development',
            self::Support => 'Support',
            self::Training => 'Training',
            self::Staging => 'Staging',
            self::Production => 'Production',
        };
    }

    /**
     * The case-insensitive spellings accepted for this environment — the canonical code, any common long
     * code, and the full name. Callers should uppercase-trim their input before matching (as
     * {@see self::tryFromAlias()} does).
     *
     * @return list<string>
     */
    public function aliases(): array
    {
        return match ($this) {
            self::Test => ['TST', 'TEST', 'test'],
            self::Development => ['DEV', 'development'],
            self::Support => ['SPT', 'support'],
            self::Training => ['TRN', 'training'],
            self::Staging => ['STG', 'staging'],
            self::Production => ['PRD', 'PROD', 'production'],
        };
    }

    /**
     * Resolve an environment from any of its accepted spellings, case-insensitively. Returns null when the
     * input matches no case's value or aliases.
     */
    public static function tryFromAlias(string $value): ?self
    {
        $needle = strtoupper(trim($value));

        foreach (self::cases() as $case) {
            if ($case->value === $needle) {
                return $case;
            }

            foreach ($case->aliases() as $alias) {
                if (strtoupper($alias) === $needle) {
                    return $case;
                }
            }
        }

        return null;
    }

    /**
     * Resolve an environment from any of its accepted spellings, or throw.
     *
     * @throws ValueError when the input matches no case's value or aliases
     */
    public static function fromAlias(string $value): self
    {
        return self::tryFromAlias($value)
            ?? throw new ValueError(sprintf('"%s" is not a valid environment.', $value));
    }

    /**
     * The six canonical three-letter codes, in case (declaration) order.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
