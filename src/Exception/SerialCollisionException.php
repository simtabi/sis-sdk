<?php

declare(strict_types=1);

namespace Simtabi\SIS\Exception;

use Simtabi\SIS\Spec;

final class SerialCollisionException extends SisConflictException
{
    protected const string SPEC_CLAUSE = Spec::DOCUMENT . ' §9';

    public static function of(string $class, ?string $scope, int $serial): self
    {
        return new self(
            sprintf(
                'Serial %d already exists for class %s%s (' . Spec::DOCUMENT . ' §9). Two things must never share an identifier.',
                $serial,
                $class,
                $scope !== null ? ' scoped to ' . $scope : '',
            ),
            ['operation' => 'issue-serial', 'class' => $class, 'scope' => $scope, 'serial' => $serial],
        );
    }
}
