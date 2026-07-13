<?php

declare(strict_types=1);

namespace Simtabi\SIS\Profile\Sim;

use Simtabi\SIS\Profile\SisProfile;

/**
 * The built-in SIM profile — the reference vocabulary of SIM-STD-0001:2026, encoding the 22 classes,
 * reserved aliases, serial policy, and alias-derivation vocabulary that the core once hardcoded. Every
 * value is lifted VERBATIM from the original SIM core, so `new Sis()` is byte-identical to the pre-profile
 * engine.
 *
 * The data itself lives in the shipped `config/sim.php` array file at the SDK repo root; this factory just
 * loads it through `SisProfile::fromFile()`. Editing the vocabulary means editing that file, not this class.
 */
final class SimProfile
{
    public static function create(): SisProfile
    {
        return SisProfile::fromFile(dirname(__DIR__, 3) . '/config/sim.php');
    }
}
