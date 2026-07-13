<?php

declare(strict_types=1);

namespace Simtabi\SIS\Policy;

use Simtabi\SIS\Exception\InvalidSubtypeException;
use Simtabi\SIS\Exception\SubjectAlreadyNamedException;
use Simtabi\SIS\Identifier\SubjectRef;
use Simtabi\SIS\Profile\ClassDefinition;

/**
 * Subject and subtype rules. One thing, one identifier (§9): a subject already named by another
 * identifier cannot be named again. And a subtype is valid only against its class's controlled
 * vocabulary (§3.7); a class with no vocabulary carries no subtype at all. The rules are pure functions of
 * the arguments, so this policy holds no profile state.
 */
final readonly class SubjectPolicy
{
    public function assertUnnamed(SubjectRef $subject, bool $alreadyNamed, ?string $namedBy = null): void
    {
        if ($alreadyNamed) {
            throw SubjectAlreadyNamedException::of($subject->type, $subject->id, $namedBy);
        }
    }

    public function assertSubtype(ClassDefinition $class, ?string $subtype): void
    {
        if ($subtype === null) {
            return;
        }

        if (!$class->hasSubtypeVocabulary()) {
            throw InvalidSubtypeException::notAllowedForClass($class->code);
        }

        if (!$class->permitsSubtype($subtype)) {
            throw InvalidSubtypeException::notPermitted($class->code, $subtype);
        }
    }
}
