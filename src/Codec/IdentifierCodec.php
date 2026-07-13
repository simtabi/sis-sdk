<?php

declare(strict_types=1);

namespace Simtabi\SIS\Codec;

use Simtabi\SIS\Contract\SisException;
use Simtabi\SIS\Exception\CheckCharacterMismatchException;
use Simtabi\SIS\Exception\ExhaustedSerialSpaceException;
use Simtabi\SIS\Exception\MalformedAliasException;
use Simtabi\SIS\Exception\MalformedIdentifierException;
use Simtabi\SIS\Exception\ScopeMismatchException;
use Simtabi\SIS\Exception\UnknownIdClassException;
use Simtabi\SIS\Grammar\IdentifierGrammar;
use Simtabi\SIS\Identifier\Alias;
use Simtabi\SIS\Identifier\Identifier;
use Simtabi\SIS\Identifier\Scope;
use Simtabi\SIS\Identifier\Serial;
use Simtabi\SIS\Profile\ClassDefinition;
use Simtabi\SIS\Profile\ClassRegister;
use Simtabi\SIS\Profile\SisProfile;
use Simtabi\SIS\Support\CheckCharacters;

/**
 * The identifier engine for one profile — the memoization point. It compiles the profile's grammar and
 * class register once, then owns every string-level operation: minting an identifier from its parts,
 * parsing and validating one, classifying an unknown string, and building the alias / scope value objects.
 *
 * The value objects it returns are dumb immutable records; all the grammar, the class lookup, and the ISO
 * 7064 check live here so they are computed once per profile rather than per call.
 */
final class IdentifierCodec
{
    private readonly IdentifierGrammar $grammar;

    private readonly ClassRegister $register;

    public function __construct(
        private readonly SisProfile $profile,
    ) {
        $this->grammar = new IdentifierGrammar($profile);
        $this->register = $profile->classes();
    }

    /** The definition for a class code, or an unknown-class failure. */
    public function class(string $code): ClassDefinition
    {
        return $this->register->class($code);
    }

    /**
     * Mint an identifier from its parts. The check characters are derived, never supplied. A class may be
     * passed as its definition or its code.
     */
    #[\NoDiscard('minting allocates an identifier; the returned value is the only copy')]
    public function mint(ClassDefinition|string $class, int $serial, ?string $scope = null, int $width = 6): Identifier
    {
        $definition = $class instanceof ClassDefinition ? $class : $this->register->class($class);

        if ($definition->isScoped() !== ($scope !== null)) {
            throw $definition->isScoped()
                ? ScopeMismatchException::scopeRequired($definition->code)
                : ScopeMismatchException::scopeForbidden($definition->code);
        }

        $serialValue = new Serial($serial);
        $padded = $serialValue->padded($width);      // validates 6 <= width <= 9

        if (!$serialValue->fitsWidth($width)) {
            throw ExhaustedSerialSpaceException::of($definition->code, $scope, $width);
        }

        $normalisedScope = $scope === null ? null : $this->scope($scope)->value;

        $issuer = $this->profile->issuer();
        $separator = $this->profile->separator();

        $core = $normalisedScope === null
            ? $issuer . $separator . $definition->code . $separator . $padded
            : $issuer . $separator . $definition->code . $separator . $normalisedScope . $separator . $padded;

        return $this->parse($core . $separator . CheckCharacters::for($core));
    }

    /**
     * Parse and validate. Rejects anything malformed, anything whose class is unknown, anything whose scope
     * does not match its class, and anything whose check characters do not verify.
     */
    public function parse(string $value): Identifier
    {
        $value = strtoupper(trim($value));

        if (preg_match($this->grammar->formS(), $value, $m) === 1) {
            [, $class, $scope, $serial, $check] = $m;
        } elseif (preg_match($this->grammar->formG(), $value, $m) === 1) {
            [, $class, $serial, $check] = $m;
            $scope = null;
        } else {
            throw MalformedIdentifierException::of($value);
        }

        $definition = $this->register->tryClass($class);

        if ($definition === null) {
            throw UnknownIdClassException::code($class);
        }

        if ($definition->isScoped() !== ($scope !== null)) {
            throw $definition->isScoped()
                ? ScopeMismatchException::scopeRequired($class)
                : ScopeMismatchException::scopeForbidden($class);
        }

        $separator = $this->profile->separator();
        $core = substr($value, 0, (int) strrpos($value, $separator));
        $expected = CheckCharacters::for($core);

        if (!CheckCharacters::verify($core, $check)) {
            throw CheckCharacterMismatchException::of($value, $expected, $check);
        }

        return new Identifier($definition, $scope, (int) $serial, $check, $value);
    }

    /** True if $value is a well-formed, check-valid identifier under this profile. */
    public function isValid(string $value): bool
    {
        try {
            $this->parse($value);

            return true;
        } catch (SisException) {
            return false;
        }
    }

    /** What kind of thing is this? Null if it is not an identifier of this profile at all. */
    public function classify(string $value): ?ClassDefinition
    {
        try {
            return $this->parse($value)->class;
        } catch (SisException) {
            return null;
        }
    }

    /** Build a mnemonic alias, validated against the profile's alias grammar (§5.1). */
    public function alias(string $value): Alias
    {
        $value = strtoupper(trim($value));

        if (!$this->profile->aliasGrammar()->matches($value)) {
            throw MalformedAliasException::of($value);
        }

        return new Alias($value);
    }

    /** Build a scope segment — a client alias, so it shares the alias grammar exactly (§5). */
    public function scope(string $value): Scope
    {
        return new Scope($this->alias($value)->value);
    }

    public function profile(): SisProfile
    {
        return $this->profile;
    }
}
