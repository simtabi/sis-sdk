<?php

declare(strict_types=1);

namespace Simtabi\SIS\Exception;

use Simtabi\SIS\Spec;

final class InvalidVersionException extends SisLogicException
{
    protected const string SPEC_CLAUSE = Spec::DOCUMENT . ' §7.2';

    public static function of(string $value): self
    {
        return new self(
            sprintf('"%s" is not a valid SIS/1 release version (' . Spec::DOCUMENT . ' §7.2).', $value),
            ['operation' => 'parse-version', 'value' => $value, 'expected' => '{ALIAS}-{semver}'],
        );
    }

    public static function productMismatch(string $a, string $b): self
    {
        return new self(
            sprintf('Cannot compare versions of different products (%s vs %s) (' . Spec::DOCUMENT . ' §7.2).', $a, $b),
            ['operation' => 'compare-version', 'product_a' => $a, 'product_b' => $b],
        );
    }
}
