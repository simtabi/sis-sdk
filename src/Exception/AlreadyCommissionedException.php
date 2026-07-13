<?php

declare(strict_types=1);

namespace Simtabi\SIS\Exception;

use Simtabi\SIS\Spec;

final class AlreadyCommissionedException extends SisStateException
{
    protected const string SPEC_CLAUSE = Spec::DOCUMENT . ' §6.3';

    public static function of(string $identifier): self
    {
        return new self(
            sprintf('%s is already commissioned (' . Spec::DOCUMENT . ' §6.3).', $identifier),
            ['operation' => 'commission', 'identifier' => $identifier, 'expected' => 'state=reserved'],
        );
    }
}
