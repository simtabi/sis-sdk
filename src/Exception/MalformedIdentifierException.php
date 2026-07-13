<?php

declare(strict_types=1);

namespace Simtabi\SIS\Exception;

use Simtabi\SIS\Spec;

final class MalformedIdentifierException extends SisLogicException
{
    protected const string SPEC_CLAUSE = Spec::DOCUMENT . ' §2';

    public static function of(string $value): self
    {
        return new self(
            sprintf('"%s" is not a well-formed SIS/1 identifier (' . Spec::DOCUMENT . ' §2).', $value),
            ['operation' => 'parse', 'value' => $value, 'expected' => 'Form G or Form S grammar'],
        );
    }
}
