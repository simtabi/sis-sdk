<?php

declare(strict_types=1);

namespace Simtabi\SIS\Contract;

use Simtabi\SIS\Codec\IdentifierCodec;
use Simtabi\SIS\Command\Minter;
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
 * The engine surface — everything a caller does with a configured register. `Sis` is the reference
 * implementation, built from a `SisProfile` (the SIM profile by default). Coding to this interface lets a
 * consumer decorate the engine (logging, metrics, multi-tenant profile selection) without depending on the
 * concrete class.
 */
interface SisEngine
{
    /** Begin building a command for an identifier of the given class (by definition, `SimClass` case, or code). */
    #[\NoDiscard('the minter builds a command that must be dispatched')]
    public function mint(SimClass|string|ClassDefinition $class): Minter;

    public function validate(string $value): bool;

    public function identify(string $value): ?ClassDefinition;

    public function parse(string $value): Identifier;

    /** Ranked alias candidates for a legal name, so a human can choose (§5.2). */
    public function aliasCandidates(string $legalName): AliasCandidates;

    /** Parse a release version (§7.2). */
    public function version(string $value): Version;

    public function class(SimClass|string $code): ClassDefinition;

    public function classes(): ClassRegister;

    public function alias(string $value): Alias;

    public function isReservedAlias(string $value): bool;

    public function profile(): SisProfile;

    public function codec(): IdentifierCodec;

    #[\NoDiscard('the returned Decision must be applied by the registrar')]
    public function decide(Command $command, Snapshot $snapshot): Decision;
}
