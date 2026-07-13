<?php

declare(strict_types=1);

namespace Simtabi\SIS\Exception;

use Simtabi\SIS\Spec;

final class UnknownIdentifierException extends SisLogicException
{
    protected const string SPEC_CLAUSE = Spec::DOCUMENT . ' §9';

    public static function of(string $identifier): self
    {
        return new self(
            sprintf('%s is not in the register (' . Spec::DOCUMENT . ' §9).', $identifier),
            ['operation' => 'resolve', 'identifier' => $identifier],
        );
    }
}
