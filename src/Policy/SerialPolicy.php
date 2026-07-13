<?php

declare(strict_types=1);

namespace Simtabi\SIS\Policy;

use Simtabi\SIS\Exception\ExhaustedSerialSpaceException;
use Simtabi\SIS\Exception\InvalidSerialException;
use Simtabi\SIS\Identifier\Serial;
use Simtabi\SIS\Profile\ClassDefinition;
use Simtabi\SIS\Profile\SerialRules;

/**
 * Serial rules — SIM-STD-0001:2026 §2.2, §3. Width bounds come from the profile's `SerialRules`; widening
 * is always safe, narrowing is forbidden. Where each class's serials start (global vs scoped, and the STD
 * exception) is carried by the class definition. The serial itself is issued atomically by the shell; this
 * policy only validates it.
 */
final readonly class SerialPolicy
{
    public function __construct(
        private SerialRules $rules,
    ) {}

    public function start(ClassDefinition $class): int
    {
        return $class->serialStart();
    }

    public function assertWidth(int $width): void
    {
        if ($width < $this->rules->minWidth || $width > $this->rules->maxWidth) {
            throw InvalidSerialException::widthOutOfRange($width);
        }
    }

    public function assertFits(ClassDefinition $class, ?string $scope, Serial $serial, int $width): void
    {
        $this->assertWidth($width);

        if (!$serial->fitsWidth($width)) {
            throw ExhaustedSerialSpaceException::of($class->code, $scope, $width);
        }
    }
}
