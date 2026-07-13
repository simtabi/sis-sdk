<?php

declare(strict_types=1);

namespace Simtabi\SIS\Tests\Sis;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Simtabi\SIS\Command\Reserve;
use Simtabi\SIS\Contract\Command;
use Simtabi\SIS\Contract\DeciderInterface;
use Simtabi\SIS\Contract\Snapshot;
use Simtabi\SIS\Decision\Decision;
use Simtabi\SIS\Enums\LifecycleState;
use Simtabi\SIS\Enums\SimClass;
use Simtabi\SIS\Identifier\Actor;
use Simtabi\SIS\Sis;
use Simtabi\SIS\Testing\InMemoryProjection;

/**
 * The engine and its decider are decorable: `withDecider()` swaps in any `DeciderInterface`, and the whole
 * `SisEngine` surface can be wrapped. Both delegate to the wrapped instance without changing behaviour.
 */
final class EngineDecorationTest extends TestCase
{
    public function test_with_decider_delegates_through_the_decorator(): void
    {
        $spy = new class(new Sis) implements DeciderInterface
        {
            public int $calls = 0;

            public function __construct(private readonly Sis $inner) {}

            public function decide(Command $command, Snapshot $snapshot): Decision
            {
                $this->calls++;

                return $this->inner->decide($command, $snapshot);
            }
        };

        $engine = (new Sis)->withDecider($spy);
        $projection = new InMemoryProjection;

        $id = $engine->codec()->mint($engine->class(SimClass::PERSON), 100001);
        $reserve = new Reserve($id, 'new hire', Actor::of('user', '1'), new DateTimeImmutable('2026-07-12T00:00:00+00:00'), 'c1', 'k1');

        $projection->apply($engine->decide($reserve, $projection->snapshotFor($reserve)));

        self::assertSame(1, $spy->calls, 'the decorator saw the dispatch');
        self::assertSame(LifecycleState::Reserved, $projection->state($id), 'and it delegated to the real decider');
    }

    public function test_the_engine_surface_is_decorable(): void
    {
        $inner = new Sis;
        $engine = new CountingEngine($inner);

        self::assertTrue($engine->validate('SIM-PRS-100001-FA'));
        self::assertSame($inner->identify('SIM-PRS-100001-FA')?->code, $engine->identify('SIM-PRS-100001-FA')?->code);
        self::assertSame(1, $engine->validateCalls);
    }
}
