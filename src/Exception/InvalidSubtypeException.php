<?php

declare(strict_types=1);

namespace Simtabi\SIS\Exception;

use Simtabi\SIS\Spec;

final class InvalidSubtypeException extends SisLogicException
{
    protected const string SPEC_CLAUSE = Spec::DOCUMENT . ' §3.7';

    public static function notPermitted(string $class, string $subtype): self
    {
        return new self(
            sprintf('"%s" is not a permitted subtype for class %s (' . Spec::DOCUMENT . ' §3.7).', $subtype, $class),
            ['operation' => 'classify', 'class' => $class, 'subtype' => $subtype],
        );
    }

    public static function notAllowedForClass(string $class): self
    {
        return new self(
            sprintf('Class %s carries no subtype vocabulary; its subtype must be null (' . Spec::DOCUMENT . ' §3.7).', $class),
            ['operation' => 'classify', 'class' => $class, 'expected' => 'subtype null'],
        );
    }
}
