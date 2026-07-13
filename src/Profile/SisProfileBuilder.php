<?php

declare(strict_types=1);

namespace Simtabi\SIS\Profile;

use InvalidArgumentException;
use Simtabi\SIS\Enums\SimClass;
use Simtabi\SIS\Spec;

/**
 * Fluent, validating builder for a `SisProfile`. Defaults are the SIM defaults, so a profile is a small
 * set of overrides: an issuer and a handful of `class()` calls is enough for a working register. `build()`
 * enforces the invariants the identifier grammar depends on — a non-empty issuer, unique three-letter
 * class codes, and a serial width band within the frozen 6–9 digits.
 */
final class SisProfileBuilder
{
    private string $issuer = '';

    private string $separator = '-';

    private ?AliasGrammar $aliasGrammar = null;

    /** @var list<string> */
    private array $reservedAliases = [];

    private ?SerialRules $serials = null;

    private float $capacityThreshold = 0.80;

    private ?AliasDerivation $aliasDerivation = null;

    private string $spec = Spec::DOCUMENT;

    private string $edition = Spec::EDITION;

    /** @var list<array{code: string, label: string, scoped: bool, usesAlias: bool, serialStart: int|null, subtypes: list<string>}> */
    private array $classes = [];

    public function issuer(string $issuer): self
    {
        $this->issuer = $issuer;

        return $this;
    }

    public function separator(string $separator): self
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * Declare a class. `serialStart` defaults to the profile's global/scoped start for the class's form. The
     * code may be given as a raw three-letter string or a {@see SimClass} case (`->class(SimClass::CLIENT, …)`).
     *
     * @param  list<string>  $subtypes
     */
    public function class(
        SimClass|string $code,
        string $label = '',
        bool $scoped = false,
        bool $usesAlias = false,
        ?int $serialStart = null,
        array $subtypes = [],
    ): self {
        $code = $code instanceof SimClass ? $code->value : strtoupper($code);

        $this->classes[] = [
            'code' => $code,
            'label' => $label === '' ? strtoupper($code) : $label,
            'scoped' => $scoped,
            'usesAlias' => $usesAlias,
            'serialStart' => $serialStart,
            'subtypes' => array_map(strtoupper(...), $subtypes),
        ];

        return $this;
    }

    public function aliasGrammar(AliasGrammar $grammar): self
    {
        $this->aliasGrammar = $grammar;

        return $this;
    }

    /** @param list<string> $reserved */
    public function reservedAliases(array $reserved): self
    {
        $this->reservedAliases = array_map(strtoupper(...), $reserved);

        return $this;
    }

    public function serials(SerialRules $serials): self
    {
        $this->serials = $serials;

        return $this;
    }

    public function capacityThreshold(float $threshold): self
    {
        $this->capacityThreshold = $threshold;

        return $this;
    }

    public function aliasDerivation(AliasDerivation $derivation): self
    {
        $this->aliasDerivation = $derivation;

        return $this;
    }

    public function spec(string $spec): self
    {
        $this->spec = $spec;

        return $this;
    }

    public function edition(string $edition): self
    {
        $this->edition = $edition;

        return $this;
    }

    public function build(): SisProfile
    {
        if (trim($this->issuer) === '') {
            throw new InvalidArgumentException('A profile requires a non-empty issuer.');
        }

        $serials = $this->serials ?? new SerialRules;

        if ($serials->minWidth < 6 || $serials->maxWidth > 9 || $serials->minWidth > $serials->maxWidth) {
            throw new InvalidArgumentException(
                sprintf('Serial width band %d–%d is outside the permitted 6–9 digits.', $serials->minWidth, $serials->maxWidth),
            );
        }

        $grammar = $this->aliasGrammar ?? new AliasGrammar;
        $derivation = $this->aliasDerivation ?? new AliasDerivation([], [], 'X', ['A', 'E', 'I', 'O', 'U'], $grammar->min, $grammar->max);

        $definitions = [];
        $seen = [];

        foreach ($this->classes as $class) {
            $code = $class['code'];

            if (preg_match('/^[A-Z]{3,4}$/', $code) !== 1) {
                throw new InvalidArgumentException(sprintf('Class code "%s" must be three or four letters A–Z.', $code));
            }

            if (isset($seen[$code])) {
                throw new InvalidArgumentException(sprintf('Class code "%s" is declared more than once.', $code));
            }

            $seen[$code] = true;
            $start = $class['serialStart'] ?? ($class['scoped'] ? $serials->scopedStart : $serials->globalStart);

            $definitions[] = new ClassDefinition(
                code: $code,
                labelText: $class['label'],
                scoped: $class['scoped'],
                aliased: $class['usesAlias'],
                firstSerial: $start,
                subtypeVocabulary: $class['subtypes'],
            );
        }

        return new SisProfile(
            issuer: strtoupper($this->issuer),
            separator: $this->separator,
            classes: new ClassRegister($definitions),
            aliasGrammar: $grammar,
            reservedAliases: $this->reservedAliases,
            serials: $serials,
            capacityThreshold: $this->capacityThreshold,
            aliasDerivation: $derivation,
            spec: $this->spec,
            edition: $this->edition,
        );
    }
}
