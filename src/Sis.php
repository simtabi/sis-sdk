<?php

declare(strict_types=1);

namespace Simtabi\SIS;

use Simtabi\SIS\Codec\IdentifierCodec;
use Simtabi\SIS\Command\Minter;
use Simtabi\SIS\Contract\Command;
use Simtabi\SIS\Contract\DeciderInterface;
use Simtabi\SIS\Contract\SisEngine;
use Simtabi\SIS\Contract\Snapshot;
use Simtabi\SIS\Decider\AttachSubjectDecider;
use Simtabi\SIS\Decider\CommissionDecider;
use Simtabi\SIS\Decider\Decider;
use Simtabi\SIS\Decider\ReleaseDecider;
use Simtabi\SIS\Decider\ReserveDecider;
use Simtabi\SIS\Decider\SupersedeDecider;
use Simtabi\SIS\Decider\TransitionDecider;
use Simtabi\SIS\Decider\VoidDecider;
use Simtabi\SIS\Decision\Decision;
use Simtabi\SIS\Enums\SimClass;
use Simtabi\SIS\Identifier\Alias;
use Simtabi\SIS\Identifier\AliasCandidates;
use Simtabi\SIS\Identifier\Identifier;
use Simtabi\SIS\Policy\AliasPolicy;
use Simtabi\SIS\Policy\SubjectPolicy;
use Simtabi\SIS\Profile\ClassDefinition;
use Simtabi\SIS\Profile\ClassRegister;
use Simtabi\SIS\Profile\Sim\SimProfile;
use Simtabi\SIS\Profile\SisProfile;
use Simtabi\SIS\Version\Version;

/**
 * SIS — the pure, framework-free entry point. The reference implementation of SIM-STD-0001:2026.
 *
 * Config-driven: `new Sis()` uses the built-in SIM profile and is byte-identical to the original hardcoded
 * core; `new Sis($profile)` runs the same total functions over a custom register vocabulary. Everything
 * here is a total function over immutable values — it builds commands and answers questions; it never
 * persists, reads a clock, logs, or dispatches. The codec, policy bundle, and decider dispatcher are built
 * once in the constructor.
 *
 *   $sis = new Sis();
 *   $reserve = $sis->mint(SimClass::PERSON)->withSerial(100001)->by($actor)->at($now)
 *                  ->correlatedBy($cid)->idempotentWith($key)->reserve('new hire');
 *
 *   $sis->validate('SIM-INV-ADIQ-000001-VY');   // true
 *   $sis->identify('SIM-PRS-100001-FA')->code;  // 'PRS'
 *   $sis->aliasCandidates('AdelsaIQ LLC');      // ranked: ADIQ, ADEL, ...
 */
final class Sis implements SisEngine
{
    public const string SPECIFICATION = Spec::DOCUMENT;

    public const string EDITION = Spec::EDITION;

    private readonly SisProfile $profile;

    private readonly IdentifierCodec $codec;

    private readonly AliasPolicy $aliasPolicy;

    private readonly DeciderInterface $decider;

    public function __construct(?SisProfile $profile = null, ?DeciderInterface $decider = null)
    {
        $this->profile = $profile ?? SimProfile::create();
        $this->codec = new IdentifierCodec($this->profile);
        $this->aliasPolicy = new AliasPolicy(
            $this->profile->aliasDerivation(),
            $this->profile->reservedAliases(),
            $this->profile->aliasGrammar(),
        );
        $this->decider = $decider ?? $this->defaultDecider();
    }

    /** A copy of this engine that dispatches through the given decider (auditing, metrics, overlays). */
    public function withDecider(DeciderInterface $decider): self
    {
        return new self($this->profile, $decider);
    }

    #[\NoDiscard('the minter builds a command that must be dispatched')]
    public function mint(SimClass|string|ClassDefinition $class): Minter
    {
        $definition = $class instanceof ClassDefinition ? $class : $this->class($class);

        return new Minter($definition, $this->codec, width: $this->profile->serials()->defaultWidth);
    }

    public function validate(string $value): bool
    {
        return $this->codec->isValid($value);
    }

    public function identify(string $value): ?ClassDefinition
    {
        return $this->codec->classify($value);
    }

    public function parse(string $value): Identifier
    {
        return $this->codec->parse($value);
    }

    public function aliasCandidates(string $legalName): AliasCandidates
    {
        return $this->aliasPolicy->candidates($legalName);
    }

    public function version(string $value): Version
    {
        return Version::parse($value);
    }

    public function class(SimClass|string $code): ClassDefinition
    {
        return $this->profile->classes()->class($code instanceof SimClass ? $code->value : $code);
    }

    public function classes(): ClassRegister
    {
        return $this->profile->classes();
    }

    public function alias(string $value): Alias
    {
        return $this->codec->alias($value);
    }

    public function isReservedAlias(string $value): bool
    {
        return $this->aliasPolicy->isReserved($value);
    }

    public function profile(): SisProfile
    {
        return $this->profile;
    }

    public function codec(): IdentifierCodec
    {
        return $this->codec;
    }

    #[\NoDiscard('the returned Decision must be applied by the registrar')]
    public function decide(Command $command, Snapshot $snapshot): Decision
    {
        return $this->decider->decide($command, $snapshot);
    }

    private function defaultDecider(): DeciderInterface
    {
        return new Decider(
            new ReserveDecider,
            new CommissionDecider($this->aliasPolicy),
            new TransitionDecider,
            new SupersedeDecider,
            new ReleaseDecider,
            new VoidDecider,
            new AttachSubjectDecider(new SubjectPolicy),
        );
    }
}
