<?php

declare(strict_types=1);

namespace Simtabi\SIS\Identifier;

use Stringable;

/**
 * The SCOPE segment of a Form S identifier — SIM-STD-0001:2026 §2, §5. It is the owning client's mnemonic
 * alias, so it shares the alias grammar exactly. A dumb, immutable record: the codec validates it against
 * the profile's alias grammar and constructs it. A project cannot move clients, so a scope is immutable
 * once commissioned.
 */
final readonly class Scope implements Stringable
{
    public function __construct(
        public string $value,
    ) {}

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
