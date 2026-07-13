<?php

declare(strict_types=1);

namespace Simtabi\SIS\Exception;

use Simtabi\SIS\Spec;

final class CheckCharacterMismatchException extends SisIntegrityException
{
    protected const string SPEC_CLAUSE = Spec::DOCUMENT . ' §4';

    public static function of(string $value, string $expected, string $actual): self
    {
        return new self(
            sprintf(
                'Check characters do not verify for "%s" (' . Spec::DOCUMENT . ' §4) — a transposition was probably introduced.',
                $value,
            ),
            [
                'operation' => 'verify-check',
                'value' => $value,
                'expected' => $expected,
                'actual' => $actual,
            ],
        );
    }
}
