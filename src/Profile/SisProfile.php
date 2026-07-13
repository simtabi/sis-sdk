<?php

declare(strict_types=1);

namespace Simtabi\SIS\Profile;

use InvalidArgumentException;
use Simtabi\SIS\Profile\Sim\SimProfile;

/**
 * A complete register vocabulary — the data the engine is built from. Everything the SIM core once
 * hardcoded (the `SIM` issuer, the 22 classes, reserved aliases, serial and alias policy) lives here as
 * data, so the same total functions serve any issuer's register. The built-in SIM profile is the default,
 * making zero-config byte-identical to the original hardcoded core.
 *
 * The identifier grammar SHAPE, the ISO 7064 check characters, and the lifecycle state machine are NOT
 * part of a profile — they are fixed by the specification and never configurable.
 */
final readonly class SisProfile
{
    /** @param list<string> $reservedAliases */
    public function __construct(
        private string $issuer,
        private string $separator,
        private ClassRegister $classes,
        private AliasGrammar $aliasGrammar,
        private array $reservedAliases,
        private SerialRules $serials,
        private float $capacityThreshold,
        private AliasDerivation $aliasDerivation,
        private string $spec,
        private string $edition,
    ) {}

    public static function builder(): SisProfileBuilder
    {
        return new SisProfileBuilder;
    }

    /** The built-in SIM profile — the reference vocabulary of SIM-STD-0001:2026. */
    public static function sim(): self
    {
        return SimProfile::create();
    }

    /**
     * Build a profile from a plain array. Only the profile keys are read: `issuer`, `separator`, `classes`,
     * `aliases`, `serials`, `capacity_threshold`, `spec`, `edition`. Everything else is left at its default.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $builder = self::builder();

        if (isset($data['issuer'])) {
            $builder->issuer(self::asString($data['issuer'], 'issuer'));
        }

        if (isset($data['separator'])) {
            $builder->separator(self::asString($data['separator'], 'separator'));
        }

        if (isset($data['serials'])) {
            $serials = self::asArray($data['serials'], 'serials');
            $defaults = new SerialRules;
            $builder->serials(new SerialRules(
                globalStart: self::asInt($serials['global_start'] ?? $defaults->globalStart, 'serials.global_start'),
                scopedStart: self::asInt($serials['scoped_start'] ?? $defaults->scopedStart, 'serials.scoped_start'),
                minWidth: self::asInt($serials['min_width'] ?? $defaults->minWidth, 'serials.min_width'),
                maxWidth: self::asInt($serials['max_width'] ?? $defaults->maxWidth, 'serials.max_width'),
                defaultWidth: self::asInt($serials['default_width'] ?? $defaults->defaultWidth, 'serials.default_width'),
            ));
        }

        if (isset($data['aliases'])) {
            self::applyAliases($builder, self::asArray($data['aliases'], 'aliases'));
        }

        if (isset($data['capacity_threshold'])) {
            $builder->capacityThreshold(self::asFloat($data['capacity_threshold'], 'capacity_threshold'));
        }

        if (isset($data['spec'])) {
            $builder->spec(self::asString($data['spec'], 'spec'));
        }

        if (isset($data['edition'])) {
            $builder->edition(self::asString($data['edition'], 'edition'));
        }

        foreach (self::asArray($data['classes'] ?? [], 'classes') as $class) {
            $class = self::asArray($class, 'classes[]');
            $subtypes = [];

            foreach (self::asArray($class['subtypes'] ?? [], 'classes[].subtypes') as $subtype) {
                $subtypes[] = self::asString($subtype, 'classes[].subtypes[]');
            }

            $builder->class(
                code: self::asString($class['code'] ?? '', 'classes[].code'),
                label: self::asString($class['label'] ?? '', 'classes[].label'),
                scoped: (bool) ($class['scoped'] ?? false),
                usesAlias: (bool) ($class['uses_alias'] ?? false),
                serialStart: isset($class['serial_start']) ? self::asInt($class['serial_start'], 'classes[].serial_start') : null,
                subtypes: $subtypes,
            );
        }

        return $builder->build();
    }

    /** Build a profile from a PHP file that returns an array. */
    public static function fromFile(string $path): self
    {
        $data = require $path;

        if (!is_array($data)) {
            throw new InvalidArgumentException(sprintf('Profile file "%s" must return an array.', $path));
        }

        /** @var array<string, mixed> $data */
        return self::fromArray($data);
    }

    public function issuer(): string
    {
        return $this->issuer;
    }

    public function separator(): string
    {
        return $this->separator;
    }

    public function classes(): ClassRegister
    {
        return $this->classes;
    }

    public function aliasGrammar(): AliasGrammar
    {
        return $this->aliasGrammar;
    }

    /** @return list<string> */
    public function reservedAliases(): array
    {
        return $this->reservedAliases;
    }

    public function serials(): SerialRules
    {
        return $this->serials;
    }

    public function capacityThreshold(): float
    {
        return $this->capacityThreshold;
    }

    public function aliasDerivation(): AliasDerivation
    {
        return $this->aliasDerivation;
    }

    public function spec(): string
    {
        return $this->spec;
    }

    public function edition(): string
    {
        return $this->edition;
    }

    /** @param array<array-key, mixed> $aliases */
    private static function applyAliases(SisProfileBuilder $builder, array $aliases): void
    {
        if (isset($aliases['grammar'])) {
            $grammar = self::asArray($aliases['grammar'], 'aliases.grammar');
            $builder->aliasGrammar(new AliasGrammar(
                min: self::asInt($grammar['min'] ?? 4, 'aliases.grammar.min'),
                max: self::asInt($grammar['max'] ?? 6, 'aliases.grammar.max'),
            ));
        }

        if (isset($aliases['reserved'])) {
            $reserved = [];

            foreach (self::asArray($aliases['reserved'], 'aliases.reserved') as $alias) {
                $reserved[] = self::asString($alias, 'aliases.reserved[]');
            }

            $builder->reservedAliases($reserved);
        }

        if (isset($aliases['derivation'])) {
            $derivation = self::asArray($aliases['derivation'], 'aliases.derivation');
            $builder->aliasDerivation(new AliasDerivation(
                legalSuffixes: self::stringList($derivation['legal_suffixes'] ?? [], 'aliases.derivation.legal_suffixes'),
                genericWords: self::stringList($derivation['generic_words'] ?? [], 'aliases.derivation.generic_words'),
                padding: self::asString($derivation['padding'] ?? 'X', 'aliases.derivation.padding'),
                vowels: self::stringList($derivation['vowels'] ?? ['A', 'E', 'I', 'O', 'U'], 'aliases.derivation.vowels'),
                min: self::asInt($derivation['min'] ?? 4, 'aliases.derivation.min'),
                max: self::asInt($derivation['max'] ?? 6, 'aliases.derivation.max'),
            ));
        }
    }

    private static function asString(mixed $value, string $key): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf('Profile key "%s" must be a string.', $key));
        }

        return $value;
    }

    private static function asInt(mixed $value, string $key): int
    {
        if (!is_int($value)) {
            throw new InvalidArgumentException(sprintf('Profile key "%s" must be an integer.', $key));
        }

        return $value;
    }

    private static function asFloat(mixed $value, string $key): float
    {
        if (!is_int($value) && !is_float($value)) {
            throw new InvalidArgumentException(sprintf('Profile key "%s" must be a number.', $key));
        }

        return (float) $value;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function asArray(mixed $value, string $key): array
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf('Profile key "%s" must be an array.', $key));
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value, string $key): array
    {
        $out = [];

        foreach (self::asArray($value, $key) as $item) {
            $out[] = self::asString($item, $key . '[]');
        }

        return $out;
    }
}
