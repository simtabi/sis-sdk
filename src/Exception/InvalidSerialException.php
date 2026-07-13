<?php

declare(strict_types=1);

namespace Simtabi\SIS\Exception;

use Simtabi\SIS\Spec;

final class InvalidSerialException extends SisLogicException
{
    protected const string SPEC_CLAUSE = Spec::DOCUMENT . ' §2.2';

    public static function notPositive(int $serial): self
    {
        return new self(
            sprintf('Serial %d is not positive (' . Spec::DOCUMENT . ' §2.2).', $serial),
            ['operation' => 'mint', 'serial' => $serial, 'expected' => 'serial > 0'],
        );
    }

    public static function belowStart(int $serial, int $start, string $class): self
    {
        return new self(
            sprintf('Serial %d for class %s is below its start %d (' . Spec::DOCUMENT . ' §3).', $serial, $class, $start),
            ['operation' => 'mint', 'serial' => $serial, 'class' => $class, 'expected' => 'serial >= ' . $start],
        );
    }

    public static function widthOutOfRange(int $width): self
    {
        return new self(
            sprintf('Serial width %d is outside the permitted 6–9 digits (' . Spec::DOCUMENT . ' §2.2).', $width),
            ['operation' => 'mint', 'width' => $width, 'expected' => '6 <= width <= 9'],
        );
    }
}
