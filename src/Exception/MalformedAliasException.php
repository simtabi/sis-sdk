<?php

declare(strict_types=1);

namespace Simtabi\SIS\Exception;

use Simtabi\SIS\Spec;

final class MalformedAliasException extends SisLogicException
{
    protected const string SPEC_CLAUSE = Spec::DOCUMENT . ' §5.1';

    public static function of(string $value): self
    {
        return new self(
            sprintf('"%s" is not a valid mnemonic alias: it must match [A-Z][A-Z0-9]{3,5} (' . Spec::DOCUMENT . ' §5.1).', $value),
            ['operation' => 'validate-alias', 'value' => $value, 'expected' => '[A-Z][A-Z0-9]{3,5}'],
        );
    }
}
