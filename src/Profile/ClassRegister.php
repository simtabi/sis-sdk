<?php

declare(strict_types=1);

namespace Simtabi\SIS\Profile;

use Simtabi\SIS\Exception\UnknownIdClassException;

/**
 * The class vocabulary of a profile, keyed by three-letter code — SIM-STD-0001:2026 §3. This is the
 * data-driven register behind `SimClass::cases()`: the SIM profile carries the built-in 22, a custom
 * profile carries whatever its builder declared.
 */
final readonly class ClassRegister
{
    /** @var array<string, ClassDefinition> code => definition */
    private array $byCode;

    /** @param iterable<ClassDefinition> $classes */
    public function __construct(iterable $classes)
    {
        $byCode = [];

        foreach ($classes as $definition) {
            $byCode[$definition->code] = $definition;
        }

        $this->byCode = $byCode;
    }

    /** The definition for a code, or an unknown-class failure. */
    public function class(string $code): ClassDefinition
    {
        return $this->byCode[strtoupper($code)] ?? throw UnknownIdClassException::code(strtoupper($code));
    }

    public function tryClass(string $code): ?ClassDefinition
    {
        return $this->byCode[strtoupper($code)] ?? null;
    }

    public function has(string $code): bool
    {
        return isset($this->byCode[strtoupper($code)]);
    }

    /** @return array<string, ClassDefinition> */
    public function all(): array
    {
        return $this->byCode;
    }

    /** @return list<string> */
    public function codes(): array
    {
        return array_keys($this->byCode);
    }
}
