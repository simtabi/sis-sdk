<?php

declare(strict_types=1);

namespace Simtabi\SIS\Exception;

use Simtabi\SIS\Spec;

final class RegisterCorruptionException extends SisIntegrityException
{
    protected const string SPEC_CLAUSE = Spec::DOCUMENT . ' §9';

    public static function of(string $identifier, string $detail): self
    {
        return new self(
            sprintf('Register row %s is corrupt: %s (' . Spec::DOCUMENT . ' §9).', $identifier, $detail),
            ['operation' => 'verify-register', 'identifier' => $identifier, 'detail' => $detail],
        );
    }
}
