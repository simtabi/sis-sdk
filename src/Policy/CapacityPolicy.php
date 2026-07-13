<?php

declare(strict_types=1);

namespace Simtabi\SIS\Policy;

use Simtabi\SIS\Profile\ClassDefinition;

/**
 * How full a serial space is, and the threshold at which a human is told — before the space is gone, not
 * after. Widening a serial is safe and cheap; discovering you cannot mint an invoice is not. The warning
 * threshold is profile data; SIM's default is 80%.
 */
final readonly class CapacityPolicy
{
    public const float DEFAULT_WARN_THRESHOLD = 0.80;

    public function __construct(
        private float $warnThreshold = self::DEFAULT_WARN_THRESHOLD,
    ) {}

    /** Fraction of the width's space consumed by $highestSerial, in [0.0, 1.0]. */
    public function usage(ClassDefinition $class, int $highestSerial, int $width): float
    {
        $start = $class->serialStart();
        $capacity = (10 ** $width) - $start;

        if ($capacity <= 0) {
            return 1.0;
        }

        return min(1.0, max(0.0, ($highestSerial - $start + 1) / $capacity));
    }

    public function isNearingExhaustion(ClassDefinition $class, int $highestSerial, int $width, ?float $threshold = null): bool
    {
        return $this->usage($class, $highestSerial, $width) >= ($threshold ?? $this->warnThreshold);
    }
}
