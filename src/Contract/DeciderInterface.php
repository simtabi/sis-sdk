<?php

declare(strict_types=1);

namespace Simtabi\SIS\Contract;

use Simtabi\SIS\Decision\Decision;

/**
 * The pure decision surface: pair a command with its snapshot and get the effects and events to apply. The
 * default dispatcher implements this, and any decorator (auditing, metrics, policy overlays) implements it
 * too — so the engine can be handed a wrapped decider without changing a single call site.
 */
interface DeciderInterface
{
    #[\NoDiscard('the returned Decision must be applied by the registrar')]
    public function decide(Command $command, Snapshot $snapshot): Decision;
}
