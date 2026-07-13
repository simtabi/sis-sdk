<?php

declare(strict_types=1);

namespace Simtabi\SIS\Profile;

/**
 * Serial-space rules — SIM-STD-0001:2026 §2.2, §3. Where a class's serials start (global vs scoped), the
 * permitted render width band, and the default width. SIM starts global serials at 100001 (never
 * advertising headcount or inventory) and scoped serials at 1, with widths 6–9 and a default of 6.
 *
 * Widening a width is always safe; narrowing is forbidden. The width band stays within the frozen 6–9
 * digits the identifier grammar allows.
 */
final readonly class SerialRules
{
    public function __construct(
        public int $globalStart = 100001,
        public int $scopedStart = 1,
        public int $minWidth = 6,
        public int $maxWidth = 9,
        public int $defaultWidth = 6,
    ) {}
}
