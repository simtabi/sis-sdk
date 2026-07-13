<?php

declare(strict_types=1);

namespace Simtabi\SIS\Tests\Sis;

use Simtabi\SIS\Codec\IdentifierCodec;
use Simtabi\SIS\Command\Minter;
use Simtabi\SIS\Contract\Command;
use Simtabi\SIS\Contract\SisEngine;
use Simtabi\SIS\Contract\Snapshot;
use Simtabi\SIS\Decision\Decision;
use Simtabi\SIS\Enums\SimClass;
use Simtabi\SIS\Identifier\Alias;
use Simtabi\SIS\Identifier\AliasCandidates;
use Simtabi\SIS\Identifier\Identifier;
use Simtabi\SIS\Profile\ClassDefinition;
use Simtabi\SIS\Profile\ClassRegister;
use Simtabi\SIS\Profile\SisProfile;
use Simtabi\SIS\Version\Version;

/**
 * A transparent decorator over any `SisEngine`, used to prove the engine surface is decorable: it forwards
 * every call to the wrapped engine and merely counts one of them.
 */
final class CountingEngine implements SisEngine
{
    public int $validateCalls = 0;

    public function __construct(
        private readonly SisEngine $inner,
    ) {}

    public function mint(SimClass|string|ClassDefinition $class): Minter
    {
        return $this->inner->mint($class);
    }

    public function validate(string $value): bool
    {
        $this->validateCalls++;

        return $this->inner->validate($value);
    }

    public function identify(string $value): ?ClassDefinition
    {
        return $this->inner->identify($value);
    }

    public function parse(string $value): Identifier
    {
        return $this->inner->parse($value);
    }

    public function aliasCandidates(string $legalName): AliasCandidates
    {
        return $this->inner->aliasCandidates($legalName);
    }

    public function version(string $value): Version
    {
        return $this->inner->version($value);
    }

    public function class(SimClass|string $code): ClassDefinition
    {
        return $this->inner->class($code);
    }

    public function classes(): ClassRegister
    {
        return $this->inner->classes();
    }

    public function alias(string $value): Alias
    {
        return $this->inner->alias($value);
    }

    public function isReservedAlias(string $value): bool
    {
        return $this->inner->isReservedAlias($value);
    }

    public function profile(): SisProfile
    {
        return $this->inner->profile();
    }

    public function codec(): IdentifierCodec
    {
        return $this->inner->codec();
    }

    public function decide(Command $command, Snapshot $snapshot): Decision
    {
        return $this->inner->decide($command, $snapshot);
    }
}
