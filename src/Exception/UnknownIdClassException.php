<?php

declare(strict_types=1);

namespace Simtabi\SIS\Exception;

use Simtabi\SIS\Spec;

final class UnknownIdClassException extends SisLogicException
{
    protected const string SPEC_CLAUSE = Spec::DOCUMENT . ' §3';

    public static function code(string $code): self
    {
        return new self(
            sprintf('"%s" is not an allocated SIS/1 class code (' . Spec::DOCUMENT . ' §3).', $code),
            ['operation' => 'classify', 'class' => $code],
        );
    }
}
