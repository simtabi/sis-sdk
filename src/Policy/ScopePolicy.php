<?php

declare(strict_types=1);

namespace Simtabi\SIS\Policy;

use Simtabi\SIS\Exception\ScopeMismatchException;
use Simtabi\SIS\Profile\ClassDefinition;

/**
 * Scope rules — SIM-STD-0001:2026 §2, §3. A Form S class requires a scope; a Form G class takes none. The
 * rule is a pure function of the class definition, so this policy holds no profile state.
 */
final readonly class ScopePolicy
{
    public function requiresScope(ClassDefinition $class): bool
    {
        return $class->isScoped();
    }

    public function assertMatches(ClassDefinition $class, ?string $scope): void
    {
        if ($class->isScoped() && $scope === null) {
            throw ScopeMismatchException::scopeRequired($class->code);
        }

        if (!$class->isScoped() && $scope !== null) {
            throw ScopeMismatchException::scopeForbidden($class->code);
        }
    }
}
