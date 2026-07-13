<?php

declare(strict_types=1);

namespace Simtabi\SIS\Exception;

use Simtabi\SIS\Spec;

final class ReservedAliasException extends SisConflictException
{
    protected const string SPEC_CLAUSE = Spec::DOCUMENT . ' §5.3';

    public static function of(string $alias): self
    {
        return new self(
            sprintf('Alias %s is reserved and cannot be allocated (' . Spec::DOCUMENT . ' §5.3).', $alias),
            ['operation' => 'assign-alias', 'alias' => $alias],
        );
    }
}
