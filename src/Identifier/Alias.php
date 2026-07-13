<?php

declare(strict_types=1);

namespace Simtabi\SIS\Identifier;

use Stringable;

/**
 * A mnemonic alias — SIM-STD-0001:2026 §5.1. Globally unique and immutable once commissioned. A dumb,
 * immutable record: the codec validates the shape against the profile's alias grammar and constructs it;
 * whether an alias is *taken* is a register question the shell answers.
 */
final readonly class Alias implements Stringable
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
