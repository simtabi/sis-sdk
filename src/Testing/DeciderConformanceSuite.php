<?php

declare(strict_types=1);

namespace Simtabi\SIS\Testing;

use DateTimeImmutable;
use Simtabi\SIS\Command\AttachSubject;
use Simtabi\SIS\Command\Commission;
use Simtabi\SIS\Command\Release;
use Simtabi\SIS\Command\Reserve;
use Simtabi\SIS\Command\Supersede;
use Simtabi\SIS\Command\Transition;
use Simtabi\SIS\Command\VoidIdentifier;
use Simtabi\SIS\Contract\Command;
use Simtabi\SIS\Enums\LifecycleState;
use Simtabi\SIS\Enums\SimClass;
use Simtabi\SIS\Exception\AliasTakenException;
use Simtabi\SIS\Exception\CannotReleaseCommissionedException;
use Simtabi\SIS\Exception\IllegalTransitionException;
use Simtabi\SIS\Exception\ReservedAliasException;
use Simtabi\SIS\Exception\SubjectAlreadyNamedException;
use Simtabi\SIS\Exception\SupersessionCycleException;
use Simtabi\SIS\Exception\TerminalStateException;
use Simtabi\SIS\Identifier\Actor;
use Simtabi\SIS\Identifier\Identifier;
use Simtabi\SIS\Identifier\SubjectRef;
use Simtabi\SIS\Profile\ClassDefinition;
use Simtabi\SIS\Sis;
use Throwable;

/**
 * The scenarios every SIS registrar must satisfy — the one thing preventing the pure core and any shell
 * from drifting. Runs the deciders against a Projection and returns a list of failures (empty = passing).
 * The core runs it against InMemoryProjection; the Eloquent shell runs the same suite against a real
 * database. Time arrives as data, so the run is deterministic. It drives a default (SIM) engine, so its
 * codec, class register, and decider bundle all come from one source.
 */
final class DeciderConformanceSuite
{
    /** @return list<string> the failures; an empty list means the projection conforms */
    public static function run(Projection $projection, DateTimeImmutable $at): array
    {
        $failures = [];
        $actor = Actor::of('user', '1');
        $n = 0;

        $engine = new Sis;
        $codec = $engine->codec();
        $classes = $engine->classes();

        $cls = static fn (SimClass $code): ClassDefinition => $classes->class($code->value);

        $dispatch = static function (Command $command) use ($projection, $engine): void {
            $projection->apply($engine->decide($command, $projection->snapshotFor($command)));
        };

        $mint = static function (ClassDefinition $class, ?string $scope, int $serial) use ($codec): Identifier {
            return $codec->mint($class, $serial, $scope);
        };

        $serial = static fn (ClassDefinition $class, ?string $scope = null): int => $projection->nextSerial($class, $scope);

        $key = static function () use (&$n): string {
            $n++;

            return 'k-' . $n;
        };

        $expect = static function (string $label, callable $fn) use (&$failures): void {
            try {
                if ($fn() !== true) {
                    $failures[] = 'expected true: ' . $label;
                }
            } catch (Throwable $e) {
                $failures[] = $label . ' threw ' . $e::class . ': ' . $e->getMessage();
            }
        };

        $expectThrows = static function (string $label, string $exceptionClass, callable $fn) use (&$failures): void {
            try {
                $fn();
                $failures[] = $label . ' did not throw ' . $exceptionClass;
            } catch (Throwable $e) {
                if (!$e instanceof $exceptionClass) {
                    $failures[] = $label . ' threw ' . $e::class . ' not ' . $exceptionClass;
                }
            }
        };

        // 1. Reserve -> Commission a person.
        $imani = $mint($cls(SimClass::PERSON), null, $serial($cls(SimClass::PERSON)));
        $dispatch(new Reserve($imani, 'founder', $actor, $at, 'c1', $key()));
        $expect('reserved person is RESERVED', static fn (): bool => $projection->state($imani) === LifecycleState::Reserved);
        $dispatch(new Commission($imani, $actor, $at, 'c1', $key()));
        $expect('commissioned person is COMMISSIONED', static fn (): bool => $projection->state($imani) === LifecycleState::Commissioned);

        // 2. Release of a commissioned identifier is forbidden — the single most important guard.
        $expectThrows('release commissioned', CannotReleaseCommissionedException::class, static function () use ($dispatch, $imani, $actor, $at, $key): void {
            $dispatch(new Release($imani, $actor, $at, 'c1', $key()));
        });

        // 3. Reserve then release a fresh reservation — the only releasable state.
        $pending = $mint($cls(SimClass::PERSON), null, $serial($cls(SimClass::PERSON)));
        $dispatch(new Reserve($pending, 'new hire', $actor, $at, 'c2', $key()));
        $dispatch(new Release($pending, $actor, $at, 'c2', $key()));
        $expect('released reservation is gone', static fn (): bool => $projection->state($pending) === null);

        // 4. Reserve then void.
        $mistake = $mint($cls(SimClass::PERSON), null, $serial($cls(SimClass::PERSON)));
        $dispatch(new Reserve($mistake, 'typo', $actor, $at, 'c3', $key()));
        $dispatch(new VoidIdentifier($mistake, 'issued in error', $actor, $at, 'c3', $key()));
        $expect('voided reservation is VOID', static fn (): bool => $projection->state($mistake) === LifecycleState::Void);

        // 5. Commission a client with alias and subject.
        $client = $mint($cls(SimClass::CLIENT), null, $serial($cls(SimClass::CLIENT)));
        $subject = SubjectRef::of('client', '42');
        $dispatch(new Reserve($client, 'onboarding AdelsaIQ', $actor, $at, 'c4', $key()));
        $dispatch(new Commission($client, $actor, $at, 'c4', $key(), $engine->alias('ADIQ'), 'AdelsaIQ LLC', $subject));
        $expect('alias ADIQ resolves to the client', static fn (): bool => (string) $projection->resolveAlias('ADIQ') === (string) $client);
        $expect('subject resolves to the client', static fn (): bool => (string) $projection->subjectIdentifier($subject) === (string) $client);

        // 6. A scoped invoice under that client.
        $invoice = $mint($cls(SimClass::INVOICE), 'ADIQ', $serial($cls(SimClass::INVOICE), 'ADIQ'));
        $dispatch(new Reserve($invoice, 'milestone 1', $actor, $at, 'c5', $key()));
        $dispatch(new Commission($invoice, $actor, $at, 'c5', $key()));
        $expect('scoped invoice commissioned', static fn (): bool => $projection->state($invoice) === LifecycleState::Commissioned);

        // 7. Lifecycle: suspend, restore, decommission.
        $dispatch(new Transition($imani, LifecycleState::Suspended, $actor, $at, 'c1', $key()));
        $expect('suspended', static fn (): bool => $projection->state($imani) === LifecycleState::Suspended);
        $dispatch(new Transition($imani, LifecycleState::Commissioned, $actor, $at, 'c1', $key()));
        $expect('restored', static fn (): bool => $projection->state($imani) === LifecycleState::Commissioned);
        $dispatch(new Transition($imani, LifecycleState::Decommissioned, $actor, $at, 'c1', $key()));
        $expect('decommissioned', static fn (): bool => $projection->state($imani) === LifecycleState::Decommissioned);

        // 8. A terminal state is terminal.
        $expectThrows('transition out of terminal', TerminalStateException::class, static function () use ($dispatch, $imani, $actor, $at, $key): void {
            $dispatch(new Transition($imani, LifecycleState::Suspended, $actor, $at, 'c1', $key()));
        });

        // 9. Voiding a commissioned identifier is illegal.
        $expectThrows('void commissioned', IllegalTransitionException::class, static function () use ($dispatch, $invoice, $actor, $at, $key): void {
            $dispatch(new VoidIdentifier($invoice, 'nope', $actor, $at, 'c5', $key()));
        });

        // 10. A reserved alias cannot be assigned.
        $reservedAliasClient = $mint($cls(SimClass::CLIENT), null, $serial($cls(SimClass::CLIENT)));
        $dispatch(new Reserve($reservedAliasClient, 'internal', $actor, $at, 'c6', $key()));
        $expectThrows('reserved alias', ReservedAliasException::class, static function () use ($dispatch, $reservedAliasClient, $actor, $at, $key, $engine): void {
            $dispatch(new Commission($reservedAliasClient, $actor, $at, 'c6', $key(), $engine->alias('SIMT')));
        });

        // 11. A taken alias cannot be reused.
        $other = $mint($cls(SimClass::CLIENT), null, $serial($cls(SimClass::CLIENT)));
        $dispatch(new Reserve($other, 'another client', $actor, $at, 'c7', $key()));
        $expectThrows('taken alias', AliasTakenException::class, static function () use ($dispatch, $other, $actor, $at, $key, $engine): void {
            $dispatch(new Commission($other, $actor, $at, 'c7', $key(), $engine->alias('ADIQ')));
        });

        // 12. One thing, one identifier: a named subject cannot be named again.
        $dup = $mint($cls(SimClass::CLIENT), null, $serial($cls(SimClass::CLIENT)));
        $dispatch(new Reserve($dup, 'duplicate subject', $actor, $at, 'c8', $key()));
        $expectThrows('subject already named', SubjectAlreadyNamedException::class, static function () use ($dispatch, $dup, $actor, $at, $key, $subject): void {
            $dispatch(new Commission($dup, $actor, $at, 'c8', $key(), null, '', $subject));
        });

        // 13. Supersession records a pointer and refuses cycles.
        $inv1 = $mint($cls(SimClass::INVOICE), 'ADIQ', $serial($cls(SimClass::INVOICE), 'ADIQ'));
        $inv2 = $mint($cls(SimClass::INVOICE), 'ADIQ', $serial($cls(SimClass::INVOICE), 'ADIQ'));
        foreach ([$inv1, $inv2] as $inv) {
            $dispatch(new Reserve($inv, 'reissue chain', $actor, $at, 'c9', $key()));
            $dispatch(new Commission($inv, $actor, $at, 'c9', $key()));
        }
        $dispatch(new Supersede($inv1, $inv2, $actor, $at, 'c9', $key()));
        $expectThrows('supersession cycle', SupersessionCycleException::class, static function () use ($dispatch, $inv2, $inv1, $actor, $at, $key): void {
            $dispatch(new Supersede($inv2, $inv1, $actor, $at, 'c9', $key()));
        });

        // 14. Attaching a subject to a reserved identifier, then blocking a duplicate.
        $asset = $mint($cls(SimClass::ASSET), null, $serial($cls(SimClass::ASSET)));
        $assetSubject = SubjectRef::of('asset', '7');
        $dispatch(new Reserve($asset, 'laptop', $actor, $at, 'c10', $key()));
        $dispatch(new AttachSubject($asset, $assetSubject, $actor, $at, 'c10', $key()));
        $expect('subject attached to reserved asset', static fn (): bool => (string) $projection->subjectIdentifier($assetSubject) === (string) $asset);

        return $failures;
    }
}
