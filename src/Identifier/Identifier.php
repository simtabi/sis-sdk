<?php

declare(strict_types=1);

namespace Simtabi\SIS\Identifier;

use Simtabi\SIS\Profile\ClassDefinition;
use Stringable;

/**
 * A SIS identifier — SIM-STD-0001:2026 §2.
 *
 *   Form G (global):  SIM-{CLASS}-{SERIAL}-{CHECK}          SIM-PRS-100001-FA
 *   Form S (scoped):  SIM-{CLASS}-{SCOPE}-{SERIAL}-{CHECK}  SIM-INV-ADIQ-000001-VY
 *
 * A dumb, immutable record: every segment is fixed at construction and there is no mutable portion —
 * state, ownership, description, and price live in the register, never in the identifier. Minting,
 * parsing, and validation are the codec's job; this value object only holds the result.
 */
final readonly class Identifier implements Stringable
{
    public function __construct(
        public ClassDefinition $class,
        public ?string $scope,
        public int $serial,
        public string $check,
        public string $value,
    ) {}

    /** The identifier without its check characters. */
    public function core(): string
    {
        $position = strrpos($this->value, '-');

        return $position === false ? $this->value : substr($this->value, 0, $position);
    }

    public function isScoped(): bool
    {
        return $this->scope !== null;
    }

    /** Whether this identifier is of the given class, compared by code. */
    public function is(string|ClassDefinition $class): bool
    {
        $code = $class instanceof ClassDefinition ? $class->code : strtoupper($class);

        return $this->class->code === $code;
    }

    /** Comparison ignores case and separators, per §2.4. */
    public function equals(self $other): bool
    {
        return $this->comparable() === $other->comparable();
    }

    public function comparable(): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->value) ?? '');
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
